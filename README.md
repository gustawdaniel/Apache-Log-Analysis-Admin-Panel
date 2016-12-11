# Analiza logów Apache z GoAccess

[TOC]

## Opis projektu

Kiedyś, usłyszałem od kolegi, że nie ma gorszego zajęcia, niż analiza logów Apache. Przeraziło mnie to bo myślałem, że to zmywanie naczyń jest najgorsze. Było to dość dawno i od tego czasu w moim życiu dużo zmieniło się na lepsze. Dzisiaj do zmywania używam zmywarki, a do analizy logów GoAccess.

W tym projekcie poznamy narzędzie pozwalające **wydobywać ciekawe informacje** z plików generowanych automatycznie podczas pracy serwera. Napiszemy **panel** udostępniający wyników analizy logów. Na koniec dodamy do niego mechanizm **content negotiation** czyli sposób na reprezentowanie tych samych obiektów za pomocą różnego typu danych.

Skład kodu

    !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

## Instalcja

GoAcces jest przystosowany do działania na wielu systemach z różnymi rodzajami logów. W tym wpisie robię założenie, że, mamy dystrybucję z repozytoriami Debiana/Ubuntu, serwer Apache2. W tym przypadku [instalacji GoAccess](https://goaccess.io/download) posłużą nam komendy:

```bash
echo "deb http://deb.goaccess.io/ $(lsb_release -cs) main" | sudo tee -a /etc/apt/sources.list.d/goaccess.list
wget -O - https://deb.goaccess.io/gnugpg.key | sudo apt-key add -
sudo apt-get update
sudo apt-get install goaccess
```

Konfiguracja polega na wycięciu komentarzy z pliku konfiguracyjnego `/etc/goaccess.conf` przy liniach zawierających wpisy:

```bash
time-format %H:%M:%S
date-format %d/%b/%Y
log-format %h %^[%d:%t %^] "%r" %s %b "%R" "%u"
```

Teraz wystarczy pobrać repozytorum z gihuba i wykonać skrypt `install.sh`

```




TU POWINO BYC REPO GIT




```

Teraz utworzymy naszą własną konfigurację do tego projektu. Jak zwykle posłużymy się plikiem `yml`.

> config/parameters.yml

```yml
config:
  apache: /var/log/apache2/*access.log
  report: report
security:
  user: user
  pass: pass
```

Własność `apache` jest to zbiór wszystkich plików z logami dostępu do poszczególnych domen, które trzymamy na serwerze. Końcówka `access.log` jest związana z przyjętą przeze mnie konwencją zgodnie z którą w konfiguracji domen przekierowuję wszystkie logi dostępu do plików `domain_access.log`. Natomiast `report` jest to lokalizacja do której będziemy zapisywać wyniki parsowania.

## Parsowanie logów

Naszym celem jest teraz wykorzystanie programu `GoAccess` do przetworzenia wszystkich logów do postaci plików html.

Do odczytywania pliku konfiguracyjnego w bashu wykorzystamy funkcję napisaną przez [Piotra Kuczyńskiego](https://gist.github.com/pkuczynski/8665367). 

> lib/parse_yml.sh

```bash
#!/usr/bin/env bash

parse_yaml() {
   local prefix=$2
   local s='[[:space:]]*' w='[a-zA-Z0-9_]*' fs=$(echo @|tr @ '\034')
   sed -ne "s|^\($s\)\($w\)$s:$s\"\(.*\)\"$s\$|\1$fs\2$fs\3|p" \
        -e "s|^\($s\)\($w\)$s:$s\(.*\)$s\$|\1$fs\2$fs\3|p"  $1 |
   awk -F$fs '{
      indent = length($1)/2;
      vname[indent] = $2;
      for (i in vname) {if (i > indent) {delete vname[i]}}
      if (length($3) > 0) {
         vn=""; for (i=0; i<indent; i++) {vn=(vn)(vname[i])("_")}
         printf("%s%s%s=\"%s\"\n", "'$prefix'",vn, $2, $3);
      }
   }'
}
```

Ta funkcja przyjmuje dwa parametry, pierwszy to nazwa pliku do parsowania, drugi jest prefixem nazw nadawanych wewnątrz naszego skryptu parametrom wydobytym z pliku `yml`. Jej zastosowanie widzimy poniżej.

```bash
#!/usr/bin/env bash

# include parse_yaml function
. lib/parse_yaml.sh

# read yaml file
eval $(parse_yaml config/parameters.yml "parameters_")

mkdir -p $parameters_config_report $parameters_config_report/html $parameters_config_report/json

arr=();

# loop over apache logs
for file in $parameters_config_apache
do
  out=$(basename "$file" .log)
  out=${out%_access}

  if [ ! -s $file ];
  then
    continue;
  fi

  echo "Processed: "$out;
  goaccess -f $file -a -o $parameters_config_report/html/$out.html;
  goaccess -f $file -a -o $parameters_config_report/json/$out.json;

  arr+=($out);
done

jq -n --arg inarr "${arr[*]}" '{ list: $inarr | split(" ") }' > $parameters_config_report/list.json
```

W tym skrypcie kolejno: załączamy powyższą funkcję, wczytujemy konfigurację do zmiennych. Następnie tworzymy katalogi w których mają się znaleźć wyniki parsowania logów, inicjalizujemy tablicę i przebiegamy pętlę po wszystkich plikach z logami. W tej pętli wydobywamy nazwę bazową pliku. Jeśli ma w nazwie `_access` to wycinamy, pomijamy puste pliki, wykonujemy na logach program goaccess który tworzy nam we wskazanym w konfiguracji katalogu pliki `html` gotowe do wyświetlania. Na końcu dodajemy do tablicy przetworzoną nazwę pliku.

Po wykonaniu pętli konwertujemy listę przetworzonych nazw do formatu `json` i zapisujemy razem z raportami. Dzięki tej liście nie będziemy musieli wykonywać pętli po katalogu w `php`.

## Backend

Logi mamy gotowe, teraz stworzymy API, które będzie je udostępniać. Nie chcemy trzymać ich w lokacji dostępnej z poziomu przeglądarki. Przeglądarka będzie miała dostęp tylko do katalogu `web` i dlatego tam umieścimy plik `api.php`. Ponieważ będziemy potrzebowali dostępu do konfiguracji zainstalujemy jeszcze parser `yml`.

```bash
composer require symfony/yaml
```

Plik z API to przede wszystkim routing. Zaczyna się jednak od podłączenia paczek, ustawienia zmiennych i nagłówków:

> web/api.php

```php
<?php

require_once __DIR__."/../vendor/autoload.php";
use Symfony\Component\Yaml\Yaml;

session_start();
$config = Yaml::parse(file_get_contents(__DIR__.'/../config/parameters.yml'));

$uri = explode('/', strtolower(substr($_SERVER['REQUEST_URI'], 1)));
$route = isset($uri[1]) ? $uri[1] : "";
$parameter = isset($uri[2]) ? $uri[2] : "";

$data = array();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST");
header('Content-Type: application/json');
```

Podpinanie konfiguracji w ten sposób [było już omawiane](http://blog.gustawdaniel.pl/2016/12/02/tesseract-ocr-i-testowanie-selekt%C3%B3w.html#kontekst). Nowością jest ustawianie sesji. Jest to na tyle sprytna funkcja, że tworzy u użytkownika plik cookie z losowym numerem sesji i jednocześnie ten numer zapisuje po stronie serwera, tak aby w zmiennej `$_SESSION` można było odwoływać się do tej konkretnej ani nie sprawdzając cookie ręcznie, ani nie martwiąc się o to, że 

Nowością jest cięcie adresu `uri` na tablicę za pomocą znaków `/`. Pierwszy jej element będzie miał wartość `api.php` dlatego wychwytujemy dwa kolejne jeśli istnieją. Ustawiamy sobie pustą tablicę `data` i na koniec dodajemy nagłówki pozwalające ominąć problemy z CORS oraz ustawić domyślny typ zwracanych danych.

W Symfony istnieją specjalne klasy `Response` i `JsonResponse`, które ułatwiają zwracanie odpowiedzi, tu jednak posłużymy się bardziej prymitywną metodą ze względu na jej prostotę. Zdefiniujemy funkcję do zwracania błędów.

```php
function returnError($code,$type,$message){
    $data["error"] = ["code" => $code, "type" => $type, "message" => $message];
    echo json_encode($data);
    die();
}
```

Warto zwrócić uwagę, że zwraca ona kody błędów, ale sama ma kod zawsze równy 200. Wyjątkiem będą błędy po stronie serwera, których nie przechwycę. Tylko w takim wypadku chcę zwracać kod błędu. Czas rozpocząć omawianie routingu. Zaczniemy od ścieżki do sprawdzania poprawności loginu. W `Symfony` odpoiada jej nie `login` ale `login_check`.

```php
switch ($route) {
        case "login": {

            if(!isset($_POST["user"]) || !isset($_POST["pass"])) {
                returnError(400,"Bad Request","Invalid form");
            } elseif($_POST["user"]!=$config["security"]["user"] || $_POST["pass"]!=$config["security"]["pass"]) {
                returnError(403,"Forbidden","Incorrect Login or Password");
            }

            $_SESSION['user'] = $config["security"]["user"];
            $data = ["state" => "loggedIn"];

        }
```

Nasz switch przyjmuje do porównań ścieżkę wpisaną po `api.php` w adresie. Ponieważ do logowania używamy `$_POST`, kontroler na tej ścieżce sprawdza czy wysłano zmienne `user` i `pass`, oraz czy są zgodne z tymi ustawionymi w konfiguracji. Zauważ, że na końcu nie dodałem instrukcji `break;`. Zrobiłem to celowo. Od razu po zalogowaniu bez wysyłania kolejnego requestu zawsze chcę dostawać listę domen, dla których Apache tworzy logi. Dlatego pod blokiem `login` umieściłem blok `report`, który ma wykonać się zarówno po wybraniu ścieżki `report` jak i po poprawnym zalogowaniu użytkownika. 

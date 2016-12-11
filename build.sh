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
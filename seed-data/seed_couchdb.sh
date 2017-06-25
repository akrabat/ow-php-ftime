#!/bin/bash -e

# This script is inspired by similar scripts in the Kitura BluePic project

# Find our current directory
current_dir="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

# Parse input parameters
database=ftime
example_url=http://whisk_admin:some_passw0rd@192.168.33.13:5984

for i in "$@"
do
case $i in
    --database=*)
    database="${i#*=}"
    shift
    ;;
    --url=*)
    url="${i#*=}"
    shift
    ;;
    *)
    ;;
esac
done

if [ -z $url ]; then
  echo "Usage:"
  echo "seed_couchdb.sh --url=<url> [--database=<database>]"
  echo "    default for --database is '$database'"
  echo ""
  echo "    format for --url: https://xxx-bluemix:yyy@zzz-bluemix.cloudant.com"
  echo "                 or : $example_url"
  exit
fi


# delete and create database to ensure it's empty
curl -s -X DELETE $url/$database
curl -s -X PUT $url/$database

# Upload design document
curl -s -X PUT "$url/$database/_design/main_design" \
    -d @$current_dir/main_design.json

# Create data
curl -s -H "Content-Type: application/json" -d @$current_dir/friends.json \
    -X POST $url/$database/_bulk_docs

echo
echo "Finished populating couchdb database '$database' on '$url'"

# FriendTime

This is a simple [OpenWhisk][1] serverless app written in [PHP][2] that will
tell you what time it is where your friend lives.

(Note that this relies on [PR 2145][3])

[1]: http://openwhisk.org
[2]: http://php.net
[3]: http://github.com/apache/incubator-openwhisk/pull/2415


**Screenshot of FTime in Slack**

![Screenshot of FTime in Slack](https://i.19ft.com/9301872c.png)


## Actions

These action exist:
 
* **`time`**: expects to respond to `/time` in Slack & will return the time for your friend or a city.
* **`setTimezone`**: expects to respond to `/settimezone` in Slack to store a friend's timezone into CouchDB with their name as the key.
* **`listTimezones`**: will show a list of timezones.
* **`findTimeByTimezone`**: given `place` which is a substring of at timezone will return the time for all timezones that match the substring.
* **`findFriendTime`**: given `name` will look up the timezone from CouchDB and then find the time for that timezone using `findTimeByTimezone`.

## Notes

* Create `settings.json` - start with `settings.json.dist`
    * Edit the information in `settings.json` to connect to your Slack and Couch DB
* Read the Makefile to see what to do:

    * `make setup` to create the package
    * `make` to build and upload all the actions


### Slack Set up

As running PHP actions natively doesn't exist outside of a [branch](https://github.com/apache/incubator-openwhisk/pull/2415)), we use [ngrok](https://ngrok.com) to enable Slack to talk to a local Vagrant install of OpenWhisk

* Create a Slack App with two slash commands:
    - `/time` command to `http://ftime.ngrok.io/api/v1/web/guest/FTime/time`
    - `/setTimezone` command to `http://ftime.ngrok.io/api/v1/web/guest/FTime/setTimezone`
* Run ngrok with command:
    
        ngrok http -subdomain ftime 192.168.33.13:10001

* seed database:

        ./seed-data/seed_couchdb.sh

### Example output

```text
$ curl -k https://192.168.33.13/api/v1/web/guest/FTime/findTimeByTimezone?place=Auckland | jq -S
{
    "pattern": "Auckland",
    "times": [
        {
            "time": "08:55:26",
            "timezone": "Pacific/Auckland"
        }
    ]
}
```

```text
$ curl -k https://192.168.33.13/api/v1/web/guest/FTime/time?text=Sara | jq -S
{
  "response_type": "in_channel",
  "text": "It's currently 6:52am for Sara."
}
```

```text
$ curl -k https://192.168.33.13/api/v1/web/guest/FTime/setTimezone?text=Andrew%20Azores | jq -S
{
  "response_type": "in_channel",
  "text": "Andrew now has a time zone of Atlantic/Azores"
}
```

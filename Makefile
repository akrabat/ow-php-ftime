
# ------------------------------------------------------------------------------
# Variables
# NAMESPACE is your OpenWhisk namespace. Default to last item in `wsk namespace list`
# SLACK_TOKEN is the Slack API token from parameters.json
NAMESPACE = $(shell wsk namespace list | tail -n1)
PACKAGE=FTime
SLACK_TOKEN = $(shell cat settings.json |  python -c "import sys, json; print json.load(sys.stdin)['slack_verification_token']")

ACTIONS = time setTime findTimeByTimezone listTimezones findFriendTime setTimezone

VPATH = actions

ZIPS=$(ACTIONS:%=build/%.zip)

# ------------------------------------------------------------------------------
.PHONY: all
all: $(ZIPS)

# build an action's zip file from it's action directory
build/%.zip: actions/%/*.php actions/common/*
	echo $(ZIPS)
	pushd actions/$(@F:.zip=) && \
	zip -q -r ../../$@ *.php ../common/* && \
	popd && \
	wsk action update $(PACKAGE)/$(@F:.zip=) build/$(@F) --kind php:7.1 --web true



# ------------------------------------------------------------------------------
# Run targets
.PHONY: curlTime curlSetTime

# make curl action=findTimeByTimezone args="place=York"
curl: $(ZIPS)
	curl -k https://192.168.33.13/api/v1/web/$(NAMESPACE)/$(PACKAGE)/$(action)?$(args) | jq -S


# ------------------------------------------------------------------------------
# Misc targets
.PHONY: lastlog setup clean

lastlog:
	wsk activation list -l1 | tail -n1 | cut -d ' ' -f1 | xargs wsk activation logs

setup:
	# Create package
	wsk package update $(PACKAGE) --param-file settings.json
	mkdir -p build

clean:
	rm -rf build/*.zip

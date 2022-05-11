#! /usr/bin/make -f

.PHONY: help build a new .phar

GIT_BRANCH := $(shell git rev-parse --abbrev-ref HEAD)
SYMFONY_CONSOLE_ARGS = --env=prod
SYMFONY_CONSOLE_BIN = bin/console
PHP = php

COLOR_RESET   = \033[0m
COLOR_INFO    = \033[32m
COLOR_COMMENT = \033[33m

## Help
help:
	@printf "${COLOR_COMMENT}Usage:${COLOR_RESET}\n"
	@printf " make [target]\n\n"
	@printf "${COLOR_COMMENT}Available targets:${COLOR_RESET}\n"
	@awk '/^[a-zA-Z\-\_0-9\.@]+:/ { \
		helpMessage = match(lastLine, /^## (.*)/); \
		if (helpMessage) { \
			helpCommand = substr($$1, 0, index($$1, ":")); \
			helpMessage = substr(lastLine, RSTART + 3, RLENGTH); \
			printf " ${COLOR_INFO}%-16s${COLOR_RESET} %s\n", helpCommand, helpMessage; \
		} \
	} \
	{ lastLine = $$0 }' $(MAKEFILE_LIST)

# SOURCE : http://stackoverflow.com/questions/10858261/abort-makefile-if-variable-not-set
# Check that given variables are set and all have non-empty values,
# die with an error otherwise.
#
# Params:
#   1. Variable name(s) to test.
#   2. (optional) Error message to print.
check_defined = \
	$(strip $(foreach 1,$1, \
		$(call __check_defined,$1,$(strip $(value 2)))))
__check_defined = \
	$(if $(value $1),, \
		$(error Undefined $1$(if $2, ($2))))


#######################
# BUILDING TASKS
#######################

## test
test:
	echo $(GIT_BRANCH) and tag $(GIT_TAG)

## Build, e.g.: GIT_TAG=x.x.x make build
build:
	$(call check_defined, GIT_TAG)
	rm -Rf ./vendor/
	composer install --no-dev

	php /usr/local/bin/box.phar compile

	git checkout gh-pages

	cp build/freeport.phar downloads/freeport-$(GIT_TAG).phar
	git add downloads/freeport-$(GIT_TAG).phar

ifeq ($(GIT_BRANCH),master)
	cp build/freeport.phar downloads/freeport.phar
	git add downloads/freeport.phar
endif

	git commit -m "Bump version $(GIT_TAG)"

	git checkout master

	git tag $(GIT_TAG)
	git push origin gh-pages
	git push --tags

	rm -Rf ./vendor/
	composer install

## install locally
install:
	mv -f build/freeport.phar /usr/local/bin/freeport
	chmod +x /usr/local/bin/freeport

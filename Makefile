export UID=$(shell id -u)

DOCKER		:= docker-compose run --rm
DOCKER_DEV	:= docker-compose -f docker-compose.yaml -f docker-compose.dev.yaml run --rm

.PHONY: build
build:
	docker-compose build

.PHONY: test
test:
	$(DOCKER) cli-ent phpunit $(with)

.PHONY: lint
lint:
	$(DOCKER) cli-ent phan $(with)

.PHONY: debug
debug:
	export XDEBUG_CONFIG="remote_autostart=1 remote_enable=1"; \
	$(DOCKER_DEV) cli-ent phpunit $(with)

.PHONY: debug-console
debug-console:
	export XDEBUG_CONFIG="remote_autostart=1 remote_enable=1"; \
	$(DOCKER_DEV) cli-ent sh $(with)

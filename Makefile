.PHONY: commands
commands:
	@grep -Po '^[a-z][^:\s]+' < Makefile | sed -e 's/^/make /'

.PHONY: dev
dev: up

.PHONY: up
up:
	docker-compose up

.PHONY: sh
sh:
	docker-compose exec app sh

.PHONY: build
build:
	docker-compose build

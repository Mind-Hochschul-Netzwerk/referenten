include .env

check-traefik:
ifeq (,$(shell docker ps -f name=^traefik$$ -q))
	$(error docker container traefik is not running)
endif

image:
	@echo "(Re)building docker image"
	docker build --no-cache -t local/$(SERVICENAME):latest .

rebuild:
	@echo "Rebuilding docker image"
	docker build -t local/$(SERVICENAME):latest .

adminer: check-traefik
	docker compose up -d adminer

database:
	docker compose up -d --force-recreate db

dev: check-traefik
	@echo "Starting DEV Server"
	docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d --force-recreate --remove-orphans

prod: check-traefik
	@echo "Starting Production Server"
	docker compose up -d --pull always --force-recreate --remove-orphans app

upgrade:
	git pull
	make prod

shell:
	docker compose exec app sh

rootshell:
	docker compose exec --user root app sh

mysql:
	@echo "docker-compose exec db mariadb --user=user --password=\"...\" database"
	@docker-compose exec db mariadb --user=user --password="$(MYSQL_PASSWORD)" database

logs:
	docker compose logs -f

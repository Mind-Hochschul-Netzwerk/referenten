check-traefik:
ifeq (,$(shell docker ps -f name=^traefik$$ -q))
	$(error docker container traefik is not running)
endif

.env:
	$(error .env is missing)

image:
	@echo "(Re)building docker image"
	docker build --no-cache -t mindhochschulnetzwerk/referenten:latest .

quick-image:
	@echo "Rebuilding docker image"
	docker build -t mindhochschulnetzwerk/referenten:latest .

dev: .env check-traefik
	@echo "Starting DEV Server"
	docker-compose -f docker-compose.base.yml -f docker-compose.dev.yml up -d --force-recreate

prod: image .env check-traefik
	@echo "Starting Production Server"
	docker-compose -f docker-compose.base.yml -f docker-compose.prod.yml up -d --force-recreate

database: .env
	docker-compose -f docker-compose.base.yml -f docker-compose.prod.yml up -d --force-recreate referenten-database

shell:
	docker-compose exec referenten sh

MYSQL_PASSWORD=$(shell grep MYSQL_PASSWORD .env | sed -e 's/^.\+=//' -e 's/^"//' -e 's/"$$//')
mysql: .env
	@echo "docker-compose exec referenten-database mysql --user=user --password=\"...\" database"
	@docker-compose exec referenten-database mysql --user=user --password="$(MYSQL_PASSWORD)" database

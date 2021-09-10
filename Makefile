SERVICENAME=$(shell grep SERVICENAME .env | sed -e 's/^.\+=//' -e 's/^"//' -e 's/"$$//')
MYSQL_PASSWORD=$(shell grep MYSQL_PASSWORD .env | sed -e 's/^.\+=//' -e 's/^"//' -e 's/"$$//')

check-traefik:
ifeq (,$(shell docker ps -f name=^traefik$$ -q))
	$(error docker container traefik is not running)
endif

.env:
	$(error file .env is missing, see .env.sample)

image:
	@echo "(Re)building docker image"
	docker build --no-cache -t mindhochschulnetzwerk/$(SERVICENAME):latest .

rebuild:
	@echo "Rebuilding docker image"
	docker build -t mindhochschulnetzwerk/$(SERVICENAME):latest .

adminer: .env check-traefik
	docker-compose up -d $(SERVICENAME)-adminer

stop-adminer:
	docker-compose stop $(SERVICENAME)-adminer

database: .env
	docker-compose up -d --force-recreate $(SERVICENAME)-database

dev: .env check-traefik
	@echo "Starting DEV Server"
	docker-compose -f docker-compose.yml -f docker-compose.dev.yml up -d --force-recreate --remove-orphans

prod: image .env check-traefik
	@echo "Starting Production Server"
	docker-compose up -d --force-recreate --remove-orphans $(SERVICENAME)

shell:
	docker-compose exec $(SERVICENAME) sh

mysql: .env
	@echo "docker-compose exec $(SERVICENAME)-database mysql --user=user --password=\"...\" database"
	@docker-compose exec $(SERVICENAME)-database mysql --user=user --password="$(MYSQL_PASSWORD)" database

logs:
	docker-compose logs -f

build:
	docker build -t admin-panel:latest .

run:
	docker run  -v ./scripts:/scripts -e APP_ENV=staging -e APP_COMPONENT=api -p8000:8000 admin-panel:latest

pre-commit-install:
	pre-commit install

pre-commit-run:
	pre-commit run --all-files

lint:
	pre-commit run --all-files

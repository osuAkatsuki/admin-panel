build:
	docker build -t admin-panel:latest .

run:
	docker run  -v ./scripts:/scripts -e APP_ENV=staging -e APP_COMPONENT=api -p8000:8000 admin-panel:latest

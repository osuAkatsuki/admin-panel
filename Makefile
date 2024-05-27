build:
	docker build -t admin-panel:latest .

run:
	docker run -p8000:8000 admin-panel:latest

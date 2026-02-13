FROM frappe/frappe-worker:latest

USER root

# Install dependencies if needed (usually frappe-worker has what we need)
RUN apt-get update && apt-get install -y \
    python3-dev \
    libmariadb-dev \
    && rm -rf /var/lib/apt/lists/*

USER frappe

# Copy apps and sites (apps.txt is required for bench commands)
COPY --chown=frappe:frappe apps /home/frappe/frappe-bench/apps
COPY --chown=frappe:frappe sites/apps.txt /home/frappe/frappe-bench/sites/apps.txt
COPY --chown=frappe:frappe sites/apps.json /home/frappe/frappe-bench/sites/apps.json

WORKDIR /home/frappe/frappe-bench

USER root
RUN ./env/bin/pip install --upgrade pip pydantic==2.10.0
RUN ./env/bin/pip install -e ./apps/frappe -e ./apps/lms
RUN chown -R frappe:frappe /home/frappe/frappe-bench/env

USER frappe
RUN bench setup requirements
RUN bench build

EXPOSE 8000 9000

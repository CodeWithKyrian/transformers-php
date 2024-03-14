#FROM python:3.11-slim-bookworm
#
#WORKDIR /app
#
## Install gcc and python3 development headers
#RUN apt-get update && \
#    apt-get install -y --no-install-recommends gcc python3-dev && \
#    rm -rf /var/lib/apt/lists/*
#
#COPY requirements.txt ./
#
#RUN pip3.11 install --no-cache-dir -r requirements.txt
#
#COPY convert.py ./
#
#ENV MODEL_ID=""
#ENV QUANTIZE=False
#ENV DEVICE="cpu"
#ENV OUTPUT_PARENT_DIR="./models/"
#ENV TASK="auto"
#
#CMD python -m convert --quantize=${QUANTIZE} --model_id=${MODEL_ID} --output_parent_dir=${OUTPUT_PARENT_DIR} --device=${DEVICE} --task=${TASK}

# Use an initial stage for building and installing dependencies
FROM python:3.11-slim-bookworm as builder

WORKDIR /build

# Install build dependencies
RUN apt-get update && \
    apt-get install -y --no-install-recommends gcc python3-dev

# Upgrade pip and set up a virtual environment
# This is where we'll install our Python packages, so they're easy to copy later
RUN pip install --upgrade pip && \
    pip install virtualenv && \
    virtualenv /build/venv

# Activate virtual environment and install dependencies
COPY requirements.txt .
RUN . /build/venv/bin/activate && \
    pip install --no-cache-dir -r requirements.txt

# Now, build the final image using python base image
FROM python:3.11-slim-bookworm

WORKDIR /app

# Copy the virtual environment from the builder stage
COPY --from=builder /build/venv /app/venv

# Copy your application code (and any other necessary files)
COPY convert.py ./

# Set environment variables
ENV MODEL_ID=""
ENV QUANTIZE=False
ENV DEVICE="cpu"
ENV OUTPUT_PARENT_DIR="./models/"
ENV TASK="auto"
ENV PATH="/app/venv/bin:$PATH"

# Use the virtual environment
CMD ["/bin/bash", "-c", "source /app/venv/bin/activate && python -m convert --quantize=${QUANTIZE} --model_id=${MODEL_ID} --output_parent_dir=${OUTPUT_PARENT_DIR} --device=${DEVICE} --task=${TASK}"]

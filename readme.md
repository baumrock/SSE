# SSE Module for ProcessWire

## Usage

## Backend

## Frontend

## Payload

### Via url params

### Via separate POST request

## Authentication

To make sse strems non-blocking we start them BEFORE Session::init

## Limitations

HTTP1.1 --> chrome max ~6 concurrent open connections (= 6 open tabs with one stream or 3 open tabs with 2 streams (eg livereload + sse))
HTTP2 seems to be able to handle over 100 connections.

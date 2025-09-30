# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-10-01

### Added
- Complete implementation of ACP (Agentic Commerce Protocol) merchant integration
- Checkout session management (create, update, get, complete, cancel)
- Webhook signature verification and event parsing for ACP payloads
- Product feed builders with support for JSON, CSV, TSV, and XML formats
- PSR-7/17/18 compatible HTTP client with HMAC signature generation
- Rich DTO models for checkout sessions, line items, payments, and orders
- Support for PHP 7.4+ with framework-agnostic design
- Comprehensive documentation and code examples

[1.0.0]: https://github.com/shopbridge/shopbridge-php/releases/tag/v1.0.0
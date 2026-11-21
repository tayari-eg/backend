---
paths:
  - app/**
  - database/**
  - routes/**
  - tests/**
---

# Code Comments

## Keep comments short and only for the non-obvious why

Comment only the non-obvious *why*: a constraint, a trap, or a reason the obvious approach is wrong. Keep it to one line where possible. Don't write comments that restate what the code does, long comment blocks, or feature or incident backstory.

Scramble PHPDoc annotations on API controllers, requests and resources (e.g. @unauthenticated, @response, summary and description lines) are documentation, not comments, and are exempt.

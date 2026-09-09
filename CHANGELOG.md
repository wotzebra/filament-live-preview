# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- The preview action group renders as a secondary button rather than a primary one. Its two
  child actions were already `gray`, so the group being `primary` made it the loudest control
  on a page whose primary action is Save. Chain `->color('primary')` on the group returned by
  `getLivePreviewAction()` to keep the old appearance.

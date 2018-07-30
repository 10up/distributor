# Contributing and Maintaining

First, thank you for taking the time to contribute!

The following is a set of guidelines for contributors as well as information and instructions around our maintenance process. The two are closely tied together in terms of how we all work together and set expectations, so while you may not need to know everything in here to submit an issue or pull request, it's best to keep them in the same document.

## Ways to contribute

Contributing isn't just writing code - it's anything that improves the project. All contributions for Distributor are managed right here on GitHub. Here are some ways you can help:

### Reporting bugs

If you're running into an issue with the plugin, please take a look through [existing issues](https://github.com/10up/distributor/issues) and [open a new one](https://github.com/10up/distributor/issues/new) if needed. If you're able, include steps to reproduce, environment information, and screenshots/screencasts as relevant.

### Suggesting enhancements

New features and enhancements are also managed via [issues](https://github.com/10up/distributor/issues).

### Pull requests

Pull requests represent a proposed solution to a specified problem. They should always reference an issue that describes the problem and contains discussion about the problem itself. Discussion on pull requests should be limited to the pull request itself, i.e. code review.

For more on how 10up writes and manages code, check out our [10up Engineering Best Practices](https://10up.github.io/Engineering-Best-Practices/).

## Workflow

The `master` branch is the development branch which means it contains the next version to be released. `stable` contains the current latest release. Always work on the `master` branch and open up PRs against `master`.

## Release instructions

1. Version bump: Bump the version number in `distributor.php`.
2. Changelog: Add/update the changelog in `README.md`
3. Merge: Make a non-fast-forward merge from `master` to `stable`. `stable` contains the current active version.
4. Git tag: Tag the release in Git and push the tag to GitHub. It should now appear under [releases](https://github.com/10up/distributor/releases) there as well.

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

The `develop` branch is the development branch which means it contains the next version to be released. `stable` contains the current latest release and `trunk` contains the corresponding stable development version. Always work on the `develop` branch and open up PRs against `develop`.

## Release instructions

1. Starting from `develop` cut a release branches for your changes.
1. Version bump: Bump the version number in `distributor.php` if it does not already reflect the version being released.  Update both the plugin "Version:" property and the plugin `DT_VERSION` constant, ensuring that it is suffixed with `-dev`.
1. New files: Ensure any new files, especially in the vendor folder, are correctly included in `gulp-tasks/copy.js`.
1. Changelog: Add/update the changelog in `CHANGELOG.md`
1. Merge: Merge the release branch into `develop`.
1. Merge: Make a non-fast-forward merge from `develop` to `trunk`. `trunk` contains the stable development version.
1. Build: Wait for the Build Stable Release Action to finish running.
1. Review: Do a review of the commit to the `stable` branch to ensure the contents of the diffs are as expected.
1. Test: Check out the `stable` branch and test it locally to ensure everything works as expected. It is recommended that you rename the existing `distributor` directory and check out `stable` fresh because switching branches does not delete files. This can be done with `git clone --single-branch --branch stable git@github.com:10up/distributor.git`
1. Release: Create a new release at https://github.com/10up/distributor/releases/new, naming the tag for the new version number and **setting the target to `stable`**. Fill in the release details and publish.
1. Check release: Wait for the Publish Release Action to complete, and then check the latest release to ensure that the ZIP has been attached as an asset. Download the ZIP and inspect the contents to be sure they match the contents of the `stable` branch.
1. Version bump (again): In the `develop` branch (`cd ../ && git checkout develop`) bump the version number in `distributor.php` to `X.Y.(Z+1)-dev`. It's okay if the next release might be a different version number; that change can be handled right before release in the first step, as might also be the case with `@since` annotations.

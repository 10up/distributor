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

1. Branch: Starting from `develop`, create a release branch named `release/X.Y.Z` for your changes.
1. Version bump: Bump the version number in `distributor.php`, `package.json`, and `readme.txt` if it does not already reflect the version being released.  In `distributor.php` update both the plugin "Version:" property and the plugin `DT_VERSION` constant.
1. New files: Ensure any new files, especially in the vendor folder, are correctly included in `gulp-tasks/copy.js`.
1. Changelog: Add/update the changelog in `CHANGELOG.md`.
1. Props: Update `CREDITS.md` file with any new contributors, confirm maintainers are accurate.
1. Readme updates: Make any other readme changes as necessary.  `README.md` is geared toward GitHub and `readme.txt` contains WordPress.org-specific content.  The two are slightly different.
1. Merge: Make a non-fast-forward merge from your release branch to `develop` (or merge the Pull Request), then do the same for `develop` into `trunk` (`git checkout trunk && git merge --no-ff develop`).  `trunk` contains the stable development version.
1. Build: Wait for the [Build Stable Release Action](https://github.com/10up/distributor/actions?query=workflow%3A%22Build+Stable+Release%22) to finish running.
1. Review: Do a review of the commit to the `stable` branch to ensure the contents of the diffs are as expected.
1. Test: Check out the `stable` branch and test it locally to ensure everything works as expected.  It is recommended that you rename the existing `distributor` directory and check out `stable` fresh because switching branches does not delete files.  This can be done with `git clone --single-branch --branch stable git@github.com:10up/distributor.git`
1. Release: Create a [new release](https://github.com/10up/distributor/releases/new), naming the tag and the release with the new version number, and **targeting the `stable` branch**.  Paste the changelog from `CHANGELOG.md` into the body of the release and include a link to the [closed issues on the milestone](https://github.com/10up/distributor/milestone/#?closed=1).  The release should now appear under [releases](https://github.com/10up/distributor/releases).
1. Check release: Wait for the [Publish Release Action](https://github.com/10up/distributor/actions?query=workflow%3A%22Publish+Release%22) to complete, and then check the latest release to ensure that the ZIP has been attached as an asset.  Download the ZIP and inspect the contents to be sure they match the contents of the `stable` branch.
1. Version bump (again): In the `develop` branch (`cd ../ && git checkout develop`) bump the version number in `distributor.php` and `readme.txt` to `X.Y.(Z+1)`.  It's okay if the next release might be a different version number; that change can be handled right before release in the first step, as might also be the case with `@since` annotations.
1. Close milestone: Edit the [milestone](https://github.com/10up/distributor/milestone/#) with release date (in the `Due date (optional)` field) and link to GitHub release (in the `Description` field), then close the milestone.
1. Punt incomplete items: If any open issues or PRs which were milestoned for `X.Y.Z` do not make it into the release, update their milestone to `X.Y.Z+1`, `X.Y+1.0`, `X+1.0.0`, or `Future Release`.

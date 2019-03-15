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

The `develop` branch is the development branch which means it contains the next version to be released. `stable` contains the current latest release and `master` contains the corresponding stable development version. Always work on the `develop` branch and open up PRs against `develop`.

## Release instructions

1. Starting from `develop` cut a release branches for your changes.
2. Version bump: Bump the version number in `distributor.php` if it does not already reflect the version being released.  Update both the plugin "Version:" property and the plugin `DT_VERSION` constant, ensuring that it is suffixed with `-dev`.
3. Changelog: Add/update the changelog in `CHANGELOG.md`
3. Merge: Make a non-fast-forward merge from your release branch to `master`. `master` contains the stable development version.
4. Build: In the `master` branch, run `npm install && npm run release`. This will create a subfolder called `release` with the `stable` branch cloned into it as a worktree and latest changes copied over. Ensure that any new files are in the `release` folder; if not, you may need to add them to `gulp-tasks/copy.js`.
5. Check: Are there any modified files, such as `distributor.pot`? If so, head back to `develop`, run all necessary tasks and commit those changes before heading back to step 3.
6. Test: Switch to running Distributor from the version in the `release` subfolder and run through a few common tasks in the UI to ensure functionality.
7. Push: From within the `release` directory, add all files and push them to `origin stable`.
8. Git tag: Tag the release as `X.Y.Z` on the `stable` branch in Git and push the tag to GitHub. It should now appear under [releases](https://github.com/10up/distributor/releases) as well.
9. Version bump (again): Bump the version number in `distributor.php` to `X.Y.(Z+1)-dev`. It's okay if the next release might be a different version number; that change can be handled right before release in the first step, as might also be the case with `@since` annotations.

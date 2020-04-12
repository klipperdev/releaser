Klipper Releaser
================

Releaser is a tool to split and release the main repository into many library repositories.

Features
--------

- Split the main repository into many repository in single command
- Split can be launched for all branches and all libraries, or selected branches and/or selected libraries
- Compatible with:
  - Git subtree
  - [Splitsh-lite](https://github.com/splitsh/lite), used by default if it is installed
- Configuration can be defined:
  - In local of the main repository with a `.klipperReleaser.json` file
  - In global of the main repository (defined by a unique key generated with the Git remote url)
  - In global for all main repositories
- All options of configuration can be edited:
  - With the `config` command
  - By opening the text editor with the config command
- Configuration can be simply validate with a command

Installation
------------

All the installation instructions are located in [documentation](doc/index.md).

Documentation
-------------

Documentation is available at [doc](doc/index.md).

Contributing
------------

Klipper Releaser is an Open Source, community-driven project.

Issues and feature requests are tracked in the [Github issue tracker](https://github.com/klipperdev/releaser/issues).

Pull Requests are tracked in the [Github pull request tracker](https://github.com/klipperdev/releaser/pulls).

License
-------

Klipper Releaser is completely free and released under the [MIT License](LICENSE).

About
-----

Klipper Releaser was originally created by [François Pluchino](https://github.com/francoispluchino).

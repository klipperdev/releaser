Usage
=====

Before to split your main repository into many read-only library repositories,
you must [configure](config.md) this tool.

### Split the main repository

```
releaser split
```

#### --depth option

By default, the tool split the current branch, and find the updated libraries in the last commit.
If you want to find the updated libraries with a more depth, you can use the options `--depth`:

```
releaser split --depth=50
```

#### --remote option

If you have multiple remote origin, you can define the remote that this tool must use:

```
releaser split --remote=target-origin
```

#### --branch option

You can specify the branches that this tool must split:

```
releaser split --branch=master --branch=1.0
```

#### --all option

If you want to force to split all configured branches, you can used this option:

```
releaser split --all
```

> **Note:** This option override the `--depth` and `--branch` options.

#### --all-lib option

If you want to force to split all configured libraries, you can used this option:

```
releaser split --all-lib
```

> **Note:** This option override the `--depth` option and the `library` argument.

#### --scratch option

If you must scratch the library repositories or force the push, you can use this option:

```
releaser split --scratch
```

#### --fetch option

By default, this tool do not fetch the branch of the main repository. If you want that
this tool fetch the main repository before the split, you can use this option:

```
releaser split --fetch
```

#### library argument

By default, the tool split the current branch, and find the updated libraries in the last commit.
If you want to explicitly define the libraries to be updated, you can list each library as an argument:

```
releaser split relative/Path/To/The/Library1 relative/Path/To/The/Library2
```

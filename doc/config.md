Configuration
=============

## Display the configuration

This command allows you to view the Releaser config settings
in either the local config file or the global config file
or the global config file for each repository (global-repo).

### Display the configuration

**For local config:**

```
releaser config --list
```

**For global repository config:**

```
releaser config --list --global-repo
```

**For global config:**

```
releaser config --list --global
```


## Manipulate the configuration

This command allows you to edit the Releaser config settings
in either the local config file or the global config file
or the global config file for each repository (global-repo).

### Edit the configuration with the editor

You can see the [JSON Schema](../res/releaser-schema.json) to build your config file:

**For local config:**

```
releaser config --editor
```

**For global repository config:**

```
releaser config --editor --global-repo
```

**For global config:**

```
releaser config --editor --global
```

### Define the library path to spit

**For local config:**

```
releaser config libraries relative/Path/To/The/Library1 git@github.com:username/repository-library1.git
```

**For global repository config:**

```
releaser config libraries relative/Path/To/The/Library1 git@github.com:username/repository-library1.git --global-repo
```

### Remove the library path to spit

**For local config:**

```
releaser config libraries relative/Path/To/The/Library1 --unset
```

**For global repository config:**

```
releaser config libraries relative/Path/To/The/Library1 --unset --global-repo
```

### Define the branch to spit

**For local config:**

```
releaser config branches master 1.0
```

**For global repository config:**

```
releaser config branches master 1.0 --global-repo
```

### Remove the branch path to spit

**For local config:**

```
releaser config branches master 1.0 --global-repo --unset
```

**For global repository config:**

```
releaser config branches master 1.0 --global-repo --unset --global-repo
```

### Define the custom binaries

It is recommended to define the custom binaries to the global config.

```
releaser config binaries git /custom/path/of/git/binary --global
```

### Remove the custom binaries

It is recommended to define the custom binaries to the global config.

```
releaser config binaries git --unset --global
```

### Other config keys

You can see the [JSON Schema](../res/releaser-schema.json) to see all available keys.

It is recommended to use the global config only for keys to be
used by all repositories such as 'home', 'data-dir' or 'binaries'.

For other keys, it is recommended to use the global repository config
or the local config.

Only the 'binaries' key cannot be added in the global repository config,
because to load the global repository config, this tool call the GIT binary
to retrieve the GIT url so that generate the unique name.

## Validate the configuration

To validate the local config, run the command:

```
releaser validate
```

To validate the local config with the strict mode, run the command:

```
releaser validate --strict
```

To validate a config file, run the command:

```
releaser validate /path/to/the/config/file.json
```

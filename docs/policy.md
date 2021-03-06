# Policy

Policies are structured YAML files that provide the human readable context for
an audit. Drutiny scans for these files and looks for files ending in `.policy.yml`.
The convention is to store these files in a directory called `Policy`.

## Policy structure

### title (required)
The human readable name and title of a given policy. The title should accurately
indicate what the audit is for in as few words as possible. An expanded context
can be given in the **description** field.

```yaml
title: Shield module is enabled
```

### name (required)
The machine readable name of the policy. This is used to call the policy in
`policy:audit` and to list in profiles. The naming convention is to use camel
case and to delineate namespaces with colons.

```yaml
name: Drupal:ShieldEnabled
```

### class (required)
The audit class used to assess the policy. The entire namespace should be given
including a leading forward slash. If this class does not exist, Drutiny will
fail when attempting to use this policy.

```yaml
class: \Drutiny\Audit\Drupal\ModuleEnabled
```

### description (required)
A human readable description of the policy. The description should be informative
enough for a human to read the description and be able to interpret the audit
findings.

This field is interpreted as [Markdown](https://daringfireball.net/projects/markdown/syntax)

```yaml
description: |
  Using the pipe (|) symbol in yaml,
  I'm able to provide my description
  across multiple lines.
```

### success (required)
The success message to provide if the audit returns successfully.
This field supports [mustache templates](https://mustache.github.io/mustache.5.html)
to display parameters given at runtime or generated by the audit.

This field is interpreted as [Markdown](https://daringfireball.net/projects/markdown/syntax)

```yaml
success: The module {{module_name}} is enabled.
```

### failure (required)
If the audit fails or is unable to complete due to an error, this message will
be displayed. This field supports [mustache templates](https://mustache.github.io/mustache.5.html)
to display parameters given at runtime or generated by the audit.

This field is interpreted as [Markdown](https://daringfireball.net/projects/markdown/syntax)

```yaml
failure: {{module_name}} is not enabled.
```

### remediation (required)
This text is displayed to advise the reader how to go about remediating the policy.
This field supports [mustache templates](https://mustache.github.io/mustache.5.html)
to display parameters given at runtime or generated by the audit.

This field is interpreted as [Markdown](https://daringfireball.net/projects/markdown/syntax)

```yaml
remediation: |
  Please enable the {{module_name}} module using drush
  `drush en {{module_name}} -y`
```

### parameters
Parameters allow a policy to define values for variables used by the Audit. They
are also used in `policy:info` to inform on the parameters available to customize.
A parameter consists of three key value pairs:

- **default** (required): The actual value to pass through to the audit.
- **type**: The data type of the parameter. Used to advise profile builders on how to use the parameter.
- **description**: A description of what the parameter is used for. Used to advise profile builders.

```yaml
parameters:
  module_name:
    default: shield
    type: string
    description: The name of the module to check is enabled.
```

### tags
The tags key is simply a mechanism to allow policies to be grouped together.
For example "Drupal 7" or "Performance".

```yaml
tags:
  - Drupal 7
  - Performance
```

### depends
Policies can depend on other policies meaning that all policies specified in this
field **must** pass successfully for this policy to pass.

```yaml
depends:
  - fs:largeFiles
  - Drupal:SyslogEnabled
```

### max_severity
Sometimes a policy may use an audit which returns an outcome more severe than
the policy really deserves. For example you may wish to use a `ModuleDisabled`
audit which returns a pass or fail. Instead your policy may only want to **warn**
instead of fail. In this case, you can set `max_severity`.

When using max_severity, outcomes less than `max_severity` can still be returned
but values more severe cannot. This excludes `Drutiny\Audit::NOT_APPLICABLE` and
`Drutiny\Audit::ERROR`.

```yaml
max_severity: 'notice'
```

#### Severity Map

Policy max_severity | Audit return value
------------------- | -----------------------------
`'notice'`          | `Drutiny\Audit::NOTICE`
`'warning'`         | `Drutiny\Audit::WARNING`
`'warning_fail'`    | `Drutiny\Audit::WARNING_FAIL`

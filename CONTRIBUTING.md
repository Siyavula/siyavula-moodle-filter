# Contributing

## Building AMD Modules

This plugin uses Moodle's AMD (Asynchronous Module Definition) system for JavaScript. Source files live in `amd/src/` and must be minified to `amd/build/` before deployment.

**Important:** Both source and built files must be committed to git. Moodle loads the minified files directly from `amd/build/` - there is no build step during deployment.

### Directory Structure

```
amd/
├── src/                    # Source files (edit these)
│   ├── initmathjax.js
│   ├── initmathjax-backward.js
│   └── updatemastery.js
└── build/                  # Minified files + source maps (commit these)
    ├── *.min.js
    └── *.min.js.map
```

### Build Process

After modifying any file in `amd/src/`, rebuild the minified version:

```bash
npx terser amd/src/FILENAME.js -o amd/build/FILENAME.min.js -c -m --source-map "url='FILENAME.min.js.map'"
```

Example for `updatemastery.js`:

```bash
npx terser amd/src/updatemastery.js -o amd/build/updatemastery.min.js -c -m --source-map "url='updatemastery.min.js.map'"
```

### AMD Module Format

All JavaScript files must use Moodle's AMD `define()` format:

```javascript
define(['dependency1', 'dependency2'], function(dep1, dep2) {
    // Module code here

    return {
        init: function() {
            // Initialization code
        }
    };
});
```

### Version Bump

After making functional changes, bump the version in `version.php`:

```php
$plugin->version = YYYYMMDDXX;  // Increment the last two digits
```

### Commit Checklist

- [ ] Source file updated in `amd/src/`
- [ ] Minified file regenerated in `amd/build/`
- [ ] Source map regenerated in `amd/build/`
- [ ] All built files staged and committed
- [ ] `version.php` bumped (if functional change)

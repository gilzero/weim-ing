# HOLY - atomic theme

- Holy is a startup theme for your project.
- It respects Brad Frost atomic design: https://bradfrost.com/blog/post/atomic-web-design/

# DRUPAL WAY

- Its based on the starterkit_theme theme.
- The goal is to style standard profile installation.

# CUSTOM THEME

1. Copy folder: themes/contrib/holy -> themes/custom/holy

2. Install NPM

	You have to install Node JS (https://nodejs.org) to your computer.

	```bash
	> cd themes/custom/holy
	> npm install
	```

3. Edit holy theme description for better orientation.

	themes/custom/holy/holy.info.yml

	```bash
	description: 'Custom theme'
	```

4. Ready for enable custom theme.

# GENERATE THEME STYLES

Use gulp to generate and update styles.

```bash
> cd themes/custom/holy
> gulp
```

# CODING STANDARDS

STYLELINT
```bash
npx stylelint "css/*.css" --config .stylelintrc-css.json
npx stylelint "sass/**/*.scss" --config .stylelintrc-scss.json
```

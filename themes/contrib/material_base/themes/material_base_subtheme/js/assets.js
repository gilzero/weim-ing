// This file required only for handling assets during webpack build.
// It shouldn't be included on the page.

// Helper function for handling assets in Webpack.
const requireAll = (r) => {
  r.keys().forEach(r);
}

// Handling images for SVG sprite in Webpack.
requireAll(require.context('../icons/', true, /\.svg$/));

// Handling images for optimization in Webpack.
requireAll(require.context('../images/', true, /\.(png|jpg|jpeg|webp|svg)$/));

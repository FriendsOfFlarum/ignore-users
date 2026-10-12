const path = require('path');
const config = require('@flarum/jest-config')();

// flarum/messages is an optional integration; its source stands in for the bundle the forum would load.
const messagesDir = path.resolve(__dirname, '../vendor/flarum/messages/js');

module.exports = {
  ...config,
  roots: [...config.roots, messagesDir],
  moduleNameMapper: {
    ...config.moduleNameMapper,
    '^ext:flarum/messages/(.*)$': path.join(messagesDir, 'src', '$1'),
  },
};

/**
 * @file
 * Installs @sentry/browser and updates library version.
 */

import Bourne from '@hapi/bourne';
import crypto from 'crypto';
import fs from 'fs';
import got from 'got';
import stream from 'stream';
import util from 'util';
import process from 'process';
import yaml from 'js-yaml';

const pipeline = util.promisify(stream.pipeline);
let version;
let releaseData;
let fetchReleaseData;
const readJson = fs.promises
  .readFile('package-lock.json', 'utf8')
  .then((contents) => {
    version =
      Bourne.parse(contents).packages['node_modules/@sentry/browser'].version;
    fetchReleaseData = got(
      `https://release-registry.services.sentry.io/sdks/sentry.javascript.browser/${version}`,
      {
        parseJson: (text) => Bourne.parse(text),
      },
    )
      .json()
      .then((parsed) => {
        releaseData = parsed;
      });
  });
const librariesFile = 'raven.libraries.yml';
let libraries;
const readYaml = fs.promises
  .readFile(librariesFile, 'utf8')
  .then((contents) => {
    libraries = yaml.load(contents);
  });
Promise.all([readJson, readYaml]).then(() => {
  libraries['sentry-browser'].version = version;
  const writeVersion = fs.promises.writeFile(
    librariesFile,
    yaml.dump(libraries),
  );
  const destination = 'js/';
  const pipelines = [];
  ['bundle.tracing.min.js', 'bundle.tracing.min.js.map'].forEach((file) => {
    const url = `https://browser.sentry-cdn.com/${version}/${file}`;
    const hash = crypto.createHash('sha384');
    pipelines.push(
      pipeline(
        got.stream(url),
        new stream.Transform({
          transform(data, encoding, callback) {
            hash.update(data);
            this.push(data);
            callback();
          },
        }),
        fs.createWriteStream(destination + file),
      ).then(async () => {
        const calculated = hash.digest('base64');
        await fetchReleaseData;
        const published = releaseData.files[file].checksums['sha384-base64'];
        console.log(`Verifying ${file}...`);
        console.log(`calculated: ${calculated}`);
        console.log(` published: ${published}`);
        if (calculated !== published) {
          throw new Error('Hash mismatch!');
        }
      }),
    );
  });
  Promise.all(pipelines.concat(writeVersion))
    .then(() => {
      console.log('Achievement unlocked.');
    })
    .catch((error) => {
      console.error(error.message);
      process.exit(1);
    });
});

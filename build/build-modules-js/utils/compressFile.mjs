import { readFile, writeFile } from 'node:fs/promises';
import { promisify } from 'node:util';
import { constants, gzip, brotliCompress } from 'node:zlib';

const gzipOpts = {
  level: constants.Z_BEST_COMPRESSION,
};

const brotliOpts = {
  params: {
    [constants.BROTLI_PARAM_MODE]: constants.BROTLI_MODE_TEXT,
    [constants.BROTLI_PARAM_QUALITY]: constants.BROTLI_MAX_QUALITY,
  },
};

const gzipPromise = promisify(gzip);
const gzipEncode = (data) => gzipPromise(data, gzipOpts);

const brotliPromise = promisify(brotliCompress);
const brotliEncode = (data) => brotliPromise(data, brotliOpts);

export const compressFile = async (file, enableBrotli) => {
  if (file.endsWith('.min.js') || file.endsWith('.min.css')) {
    try {
      const data = await readFile(file);
      await writeFile(`${file}.gz`, await gzipEncode(data));
      if (enableBrotli) {
        await writeFile(`${file}.br`, await brotliEncode(data));
      }
      // eslint-disable-next-line no-console
      console.log(file);
    } catch (err) {
      // eslint-disable-next-line no-console
      console.info(`Error on ${file}: ${err.code}`);
    }
  }
};

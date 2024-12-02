import { parse } from 'path';
import fs from 'fs/promises';
import sharp from "sharp"

const isSvg = (filename) => filename.toLowerCase().endsWith('.svg')

const imageDir = `./dev/${process.env.APP_MODULE}/images`
const targetDir = `./www/dist/${process.env.APP_MODULE}/images`
let filenames =  []

console.log('\n\u001b[34mOptimizing images...')

try {
  filenames = await fs.readdir(imageDir);
  await fs.mkdir(targetDir, { recursive: true })
} catch (e) {}

if (filenames.length === 0) {
  console.log(`\u001b[32mModule ${process.env.APP_MODULE} does not contain any images to optimize.`)
}

for (const filename of filenames) {
  const path = `${imageDir}/${filename}`
  const file = await fs.readFile(path)
  const { base } = parse( filename )

  // don't process SVG images
  if (isSvg(base)) {
    console.log(`\u001b[35m  An SVG file '\u001b[36m${base}\u001b[35m' was saved without optimizing`)
    await fs.copyFile(path, `${targetDir}/${base}`)
    continue
  }

  try {
    // attempt to optimize all files inside the folder
    console.log(`\u001b[39m  Optimizing '${base}'...`)
    await sharp(file)
      .jpeg({ progressive: true, quality: 80, force: false })
      .png({ progressive: true, quality: 80, force: false })
      .webp({ quality: 80, force: false })
      .toFile(`${targetDir}/${base}`)
    console.log(`\x1b[1A\x1b[2K\u001b[32m✓ File '\u001b[36m${base}\u001b[32m' optimized`)
  } catch (e) {
    // copy unsupported images without processing
    console.log(`\x1b[1A\x1b[2K\u001b[33m  File '\u001b[36m${filename}\u001b[33m' was saved without optimizing`)
    await fs.copyFile(path, `${targetDir}/${filename}`)
  }
}

// reset console color
console.log('\u001b[39m')
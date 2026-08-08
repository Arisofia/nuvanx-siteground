import fs from 'node:fs/promises';
import path from 'node:path';

/**
 * Recursively scans matching files in a directory and aggregates their violations.
 * @param {string} rootDir - The directory to scan.
 * @param {string[]} extensions - File extensions to include.
 * @param {Function} scanFile - Function that scans each matching file.
 * @return {Array} The combined violations from all matching files.
 */
export async function scanDirectory(rootDir, extensions, scanFile) {
  const files = [];

  async function walk(currentDir) {
    const entries = await fs.readdir(currentDir, { withFileTypes: true });
    for (const entry of entries) {
      const fullPath = path.join(currentDir, entry.name);
      if (entry.isDirectory()) {
        if (entry.name !== 'node_modules' && entry.name !== 'vendor') await walk(fullPath);
      } else if (extensions.includes(path.extname(entry.name))) {
        files.push(fullPath);
      }
    }
  }

  await walk(rootDir);
  const violations = [];
  for (const file of files) violations.push(...await scanFile(file));
  return violations;
}

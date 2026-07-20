import fs from 'fs';
import path from 'path';

const dir = 'wp-content/themes/nuvanx-medical/assets/css/';
const files = fs.readdirSync(dir).filter(f => f.endsWith('.css') && !f.endsWith('.min.css'));

const replacements = [
  { val: '28px', token: 'calc(var(--nvx-space-1) * 3.5)' },
  { val: '18px', token: 'calc(var(--nvx-space-1) * 2.25)' },
  { val: '22px', token: 'calc(var(--nvx-space-1) * 2.75)' },
  { val: '160px', token: 'calc(var(--nvx-space-10) * 2)' },
  { val: '3px', token: 'calc(var(--nvx-space-1) * 0.375)' },
  { val: '5px', token: 'calc(var(--nvx-space-1) * 0.625)' },
  { val: '6px', token: 'calc(var(--nvx-space-1) * 0.75)' },
  { val: '10px', token: 'calc(var(--nvx-space-1) * 1.25)' },
  { val: '25px', token: 'calc(var(--nvx-space-1) * 3.125)' },
  { val: '46px', token: 'calc(var(--nvx-space-1) * 5.75)' },
  { val: '54px', token: 'calc(var(--nvx-space-1) * 6.75)' },
  { val: '104px', token: 'calc(var(--nvx-space-1) * 13)' },
  { val: '300px', token: 'calc(var(--nvx-space-1) * 37.5)' },
  { val: '768px', token: 'calc(var(--nvx-space-12) * 8)' },
  { val: '800px', token: 'calc(var(--nvx-space-12) * 8.333)' },
  { val: '820px', token: 'var(--nvx-readable)' },
  { val: '-1px', token: 'calc(var(--nvx-border-hairline) * -1)' },
  { val: '-2px', token: 'calc(var(--nvx-space-1) * -0.25)' },
  { val: '-4px', token: 'calc(var(--nvx-space-1) * -0.5)' },
  { val: '-6px', token: 'calc(var(--nvx-space-1) * -0.75)' },
  { val: '1.5px', token: 'calc(var(--nvx-border-hairline) * 1.5)' },
  { val: '4px', token: 'calc(var(--nvx-space-1) / 2)' }
];

let totalReplaced = 0;

for (const file of files) {
  const filePath = path.join(dir, file);
  let content = fs.readFileSync(filePath, 'utf8');
  let originalContent = content;

  for (const { val, token } of replacements) {
    const escaped = val.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const regex = new RegExp(`(^|[^\\w.-])${escaped}(?=$|[^\\w.-])`, 'g');
    content = content.replace(regex, (_, prefix) => `${prefix}${token}`);
  }

  if (content !== originalContent) {
    fs.writeFileSync(filePath, content, 'utf8');
    totalReplaced++;
    console.log(`Replaced pixels in ${file}`);
  }
}

console.log(`Finished replacing in ${totalReplaced} files.`);

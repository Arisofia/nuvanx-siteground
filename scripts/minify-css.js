const fs = require('fs');
const path = require('path');

const files = process.argv.slice(2);

function minify(css) {
	return css
		.replace(/\/\*[\s\S]*?\*\//g, '')
		.replace(/\s+/g, ' ')
		.replace(/\s*([{}:;,>+~])\s*/g, '$1')
		.replace(/;}/g, '}')
		.trim();
}

for (const file of files) {
	const src = path.resolve(file);
	const css = fs.readFileSync(src, 'utf8');
	const out = src.replace(/\.css$/, '.min.css');
	fs.writeFileSync(out, minify(css));
	console.log(path.basename(out), fs.statSync(out).size);
}
#!/usr/bin/env node
import fs from 'node:fs';

const [, , schemaPath, dataPath] = process.argv;
if (!schemaPath || !dataPath) throw new Error('Usage: validate-routes-schema.mjs <schema> <data>');
const schema = JSON.parse(fs.readFileSync(schemaPath, 'utf8'));
const data = JSON.parse(fs.readFileSync(dataPath, 'utf8'));
const routeSchema = schema.patternProperties?.['^/.*'];
if (!routeSchema || schema.type !== 'object') throw new Error('Unsupported routes schema contract');
if (!data || Array.isArray(data) || typeof data !== 'object') throw new Error('routes.json must be an object');
const allowed = new Set(Object.keys(routeSchema.properties || {}));
for (const [route, entry] of Object.entries(data)) {
  if (!route.startsWith('/')) throw new Error(`Invalid route key: ${route}`);
  if (!entry || Array.isArray(entry) || typeof entry !== 'object') throw new Error(`Route ${route} must map to an object`);
  for (const [key, value] of Object.entries(entry)) {
    if (!allowed.has(key)) throw new Error(`Route ${route} has unsupported property ${key}`);
    const rule = routeSchema.properties[key] || {};
    if (rule.type === 'string' && typeof value !== 'string') throw new Error(`Route ${route}.${key} must be a string`);
    if (rule.type === 'integer' && !Number.isInteger(value)) throw new Error(`Route ${route}.${key} must be an integer`);
    if (rule.enum && !rule.enum.includes(value)) throw new Error(`Route ${route}.${key} has invalid value ${value}`);
  }
}
console.log(`ROUTES_SCHEMA=PASS routes=${Object.keys(data).length}`);

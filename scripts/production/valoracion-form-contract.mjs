function stripScriptAndStyleText(html) {
  return String(html || '')
    .replace(/<script\b[^>]*>[\s\S]*?<\/script\s*>/gi, '')
    .replace(/<style\b[^>]*>[\s\S]*?<\/style\s*>/gi, '');
}

export function legacyValoracionDirectFormTags(html) {
  const markup = stripScriptAndStyleText(html);
  const formTags = markup.match(/<form\b[^>]*>/gi) || [];
  return formTags.filter((tag) => {
    const classMarker = /\bclass\s*=\s*(?:"[^"]*\bnvx-valoracion-direct-form\b[^"]*"|'[^']*\bnvx-valoracion-direct-form\b[^']*')/i.test(tag);
    const dataMarker = /\bdata-nvx-direct-form(?:\s*=\s*(?:"[^"]*"|'[^']*'|[^\s>]+))?(?=\s|\/?>)/i.test(tag);
    return classMarker || dataMarker;
  });
}

export function hasLegacyValoracionDirectForm(html) {
  return legacyValoracionDirectFormTags(html).length > 0;
}

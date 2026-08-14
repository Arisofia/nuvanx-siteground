'use strict';

const { google } = require('googleapis');

function formatDate(date) {
  return date.toISOString().split('T')[0];
}

/**
 * Returns dynamic date ranges taking typical Google Search Console ~3 day reporting latency into account.
 */
function getGscDateRanges() {
  const now = new Date();
  const end = new Date(now);
  end.setDate(end.getDate() - 3); // 3 days reporting lag

  const start30 = new Date(end);
  start30.setDate(start30.getDate() - 29); // 30 days inclusive

  const start7 = new Date(end);
  start7.setDate(start7.getDate() - 6); // 7 days inclusive

  const prev7End = new Date(start7);
  prev7End.setDate(prev7End.getDate() - 1);
  const prev7Start = new Date(prev7End);
  prev7Start.setDate(prev7Start.getDate() - 6); // 7 days inclusive

  return {
    endDate: formatDate(end),
    startDate30: formatDate(start30),
    startDate7: formatDate(start7),
    prev7Start: formatDate(prev7Start),
    prev7End: formatDate(prev7End),
  };
}

function createGscClient() {
  const auth = new google.auth.GoogleAuth({
    scopes: ['https://www.googleapis.com/auth/webmasters.readonly'],
  });
  return google.searchconsole({ version: 'v1', auth });
}

async function queryGsc(sc, siteUrl, requestBody) {
  const res = await sc.searchanalytics.query({
    siteUrl,
    requestBody,
  });
  return res.data.rows || [];
}

module.exports = {
  formatDate,
  getGscDateRanges,
  createGscClient,
  queryGsc,
};

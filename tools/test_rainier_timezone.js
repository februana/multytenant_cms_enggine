const fs = require('fs');
const vm = require('vm');
const source = fs.readFileSync(__dirname + '/../themes/rainier/original/invite-1-adapter.js', 'utf8');
const prefix = source.slice(0, source.indexOf('function startCountdown'));
const context = { console, Intl, Date, URLSearchParams };
vm.createContext(context);
vm.runInContext(prefix + '\nthis.parseEventDate = parseEventDate; this.formatDisplayDate = formatDisplayDate;', context);

const instant = context.parseEventDate('2030-01-01', '12:00', 'Asia/Jakarta');
if (!instant || instant.toISOString() !== '2030-01-01T05:00:00.000Z') {
  throw new Error(`Unexpected Asia/Jakarta instant: ${instant && instant.toISOString()}`);
}
const display = context.formatDisplayDate(instant.toISOString(), 'en-US', 'Asia/Jakarta');
if (!display.includes('January 1, 2030')) {
  throw new Error(`Unexpected display date: ${display}`);
}
const utc = context.parseEventDate('2030-01-01', '12:00', 'UTC');
if (!utc || utc.toISOString() !== '2030-01-01T12:00:00.000Z') {
  throw new Error(`Unexpected UTC instant: ${utc && utc.toISOString()}`);
}
console.log('PASS: Rainier IANA timezone conversion and display formatting');

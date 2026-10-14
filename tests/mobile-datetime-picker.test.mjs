import test from 'node:test';
import assert from 'node:assert/strict';

globalThis.document = { querySelectorAll: () => [] };
const { calendarDays } = await import('../resources/js/mobile-datetime-picker.js');
delete globalThis.document;

test('calendar starts on Monday and covers all days of a leap February', () => {
    const days = calendarDays(2028, 1);
    assert.equal(days.length, 42);
    assert.equal(days[0].getDay(), 1);
    assert.equal(days.filter(day => day.getMonth() === 1).length, 29);
    assert.ok(days.some(day => day.getMonth() === 1 && day.getDate() === 29));
});

test('month navigation handles year boundaries without losing days', () => {
    const december = calendarDays(2026, 11);
    const january = calendarDays(2026, 12);
    assert.ok(december.some(day => day.getFullYear() === 2027));
    assert.equal(january.filter(day => day.getFullYear() === 2027 && day.getMonth() === 0).length, 31);
    assert.equal(january[0].getDay(), 1);
});

test('a month starting on Sunday still includes its final date', () => {
    const days = calendarDays(2026, 1);
    assert.equal(days[0].getDay(), 1);
    assert.equal(days[6].getDate(), 1);
    assert.equal(days.filter(day => day.getMonth() === 1).length, 28);
});

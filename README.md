# moodle-local_lsu_evalfixer

A scheduled task finds an administrator-defined URL in course section summaries. It replaces that URL and applies a section name override. An optional action attempts non-forced deletion first; if deletion is refused or blocked, it updates the section instead.

## Installation

1. Install the ZIP using **Site administration → Plugins → Install plugins**. For a manual installation, place the included `lsu_evalfixer` directory under Moodle's `local` directory (under `public/local` on installations using the public webroot layout).
2. Visit **Site administration → Notifications** and complete installation or upgrade.
3. Open **Site administration → Plugins → Local plugins → LSU evaluation fixer**.
4. Set the search URL, replacement URL and section name override.
5. Select the targeting filter and action. Enable processing and leave **Dry run** on.
6. Run **Fix LSU evaluation course sections** from the scheduled tasks page if Moodle's **Run now** facility is available, or let the site's normal cron execute it.
7. Review the task log. Turn dry run off when you want the next execution to apply changes.

No shell access is required for configuration or normal cron execution.

## Settings

| Setting | Default | Meaning |
| --- | --- | --- |
| Enable scheduled processing | Off | Master switch; disabled means no processing |
| Dry run | On | Log intended operations without writes or explicit cache clearing |
| URL to find | Empty | Required absolute HTTP(S) URL |
| Replacement URL | Empty | Required in both modes, including deletion fallback |
| Section name override | Empty | Required plain text, maximum 255 characters |
| Only target empty, unrestricted sections | On | Apply the exact filter below to all changes |
| Action | Replace URL and rename | Alternatively, try deletion and fall back to updating |

Replacement must differ from the search URL and must not contain it for PAINFULLY obvious reasons. Blank replacements and name-only changes with identical URLs are not supported. Invalid settings fail the task before it changes data.

## Matching and scope

Matching is a **case-sensitive literal substring** anywhere in the summary, including link attributes and visible text. All occurrences are replaced. It can match part of a longer URL; it does not parse hyperlinks or compare hosts. There are no regex matches, redirect checks or URL-normalisation rules.

Enter URLs with normal `&` characters. The task also recognises the normal HTML-escaped form using `&amp;`. It uses a single `strtr()` call to replace raw and HTML forms without replacing newly inserted text again. When raw and escaped search forms are identical, the replacement is HTML-escaped. This also means an ampersand may be stored as `&amp;` in a plain-text summary; the task does not branch on summary format. Other entity spellings, such as `&#38;`, are not normalised.

The optional filter requires **both**:

```text
sequence === ''
AND
(availability IS NULL OR availability === '{"op":"&","c":[],"showc":[]}')
```

`NULL` sequence, whitespace, empty availability strings, literal `null` text and differently formatted JSON do not qualify. This follows the exact original request rather than the broader JSON interpretation in the alternate uploaded version.

All ordinary courses are eligible, including hidden and past courses. The site front-page course is excluded. Section 0 can be updated if it matches the selected filter, but can never be deleted. There is no date/category/course selector or per-run section limit.

## Deletion and fallback

A deletion attempt requires:

- The matching URL and the exact empty/unrestricted conditions, even if the filter is off.
- A section number greater than zero.
- No nonempty `component` field indicating a delegated section.
- No `course_modules` records pointing to it, including orphaned or pending-deletion activities.
- A course format that permits deleting the section.

The only deletion call is:

```php
course_delete_section($course, $section, false, false);
```

Deletion is **never forced** and the summary is never cleared to make deletion possible.
Moodle's standard formats normally refuse non-forced deletion of a nonempty summary.
A summary containing the search URL is nonempty, so those sections normally receive the fallback update rather than being deleted. Custom formats may behave differently.

Blocked deletion, a false return, or a caught deletion error leads to URL/name replacement.
The section is re-read and rechecked before that fallback; a missing section is not recreated and a section that no longer matches is skipped. If the enabled targeting filter excludes a section, it is left completely untouched.

Dry-run logs say **WOULD TRY NON-FORCED DELETE; UPDATE IF REFUSED** when an attempt is eligible. This is not a promise that Moodle will delete it. Other targets report an update.

## Caches, errors and concurrent edits

Each live course ends with `rebuild_course_cache($courseid, true)`, including after errors. This clears course information for rebuilding on demand; it does not purge all site caches. Caches are also cleared before core mutation calls to avoid using stale section information. Dry runs perform no explicit cache clearing or mutations.

One failed section does not prevent later sections from being processed. Errors are logged, and the task fails at the end if any section/course/cache operation failed, allowing Moodle to report the failure. Successfully completed earlier changes are not rolled back. A refused deletion followed by a successful update is a successful fallback, not a task error.

A Moodle lock prevents overlapping executions using this task's lock. It cannot prevent teacher edits, imports or unrelated plugins. The checks and core API calls are not a single atomic operation. Run live deletion during a controlled period with competing edits paused.

For simplicity, the task loads candidate **course IDs** in one query, then loads section IDs for one course at a time and reads each summary separately. It does not load every matching summary at once. It is not paginated and has no saved checkpoint. The URL search scans summary text, so schedule off peak on large sites.

## Schedule and logs

New installations default to a randomized minute during 02:00 every day, using Moodle's scheduler. Change this in **Site administration → Server → Tasks → Scheduled tasks**.
Normal Moodle cron must be working.

The log contains course/section IDs, proposed or completed actions, cache status, errors, and final changed/error totals. It does not intentionally print URLs or full summaries. Exception messages originate from Moodle and may contain additional details.

There is no custom CLI in this edition. If shell access is available, Moodle's existing scheduled-task runner can execute it from the webroot:

```bash
php admin/cli/scheduled_task.php --execute='\local_lsu_evalfixer\task\fix_sections'
```

This obeys the enabled and dry-run settings.

## Compatibility and validation

Requires Moodle 4.5 or later; designed for 4.5–5.3 range using core APIs.

## License

GNU GPL version 3.

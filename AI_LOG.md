# AI use log

**Tool and role.** I used ChatGPT to help interpret the brief, plan the implementation, draft Laravel code and PowerShell scripts, and diagnose local errors. I ran the commands on Windows, reviewed the terminal output and Git diffs, and made the commits in the repository.

**How I directed the work.** I asked for a fresh project, small stages with a real commit history, compact Laravel code, and PowerShell blocks for file changes. I verified each stage before proceeding to authentication, users, posts, the welcome email, and Perth weather.

**Decisions.** The API uses Sanctum bearer tokens, MySQL for local runs, and SQLite in memory for tests. Post ownership comes from the authenticated account and writes are checked by a policy. I used the free Open-Meteo endpoint, a 15-minute cache, an hourly refresh job, and the log mailer for local email verification.

**What I caught and checked.** Composer rejected a target containing `.git`, so I generated the scaffold in a temporary folder and kept the existing repository history. Docker Desktop's engine initially prevented MySQL from starting. Once it was running, the migrations succeeded. A failed login exposed a missing `HasApiTokens` trait. After applying the fix, I tested login, logout, and rejection of the revoked token. A final review found that creating a post could serialize its loaded user relation. The response now excludes that relation and the feature test checks the response shape.

**Verification.** I ran the feature suite, verified the MySQL migrations, ran the welcome-email job through the database worker, checked the hourly schedule, and made a live request to the weather endpoint. The weather failure tests use simulated upstream responses.

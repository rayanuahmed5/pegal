# Your first commit

For every teammate who works on the repository. Use **your own GitHub account**, so the commit shows your name. Commands are for PowerShell on Windows (Laragon); on other systems they are the same.

1. **Accept the invitation** to the repository (e-mail from GitHub, or https://github.com/rayanuahmed5/pegal/invitations).
2. **Tell Git who you are** (once per computer). Use the e-mail of your GitHub account, so GitHub links the commit to you:

   ```
   git config --global user.name "Your Name"
   git config --global user.email "you@example.com"
   ```

3. **Clone and open the folder** (in Laragon, `C:\laragon\www`):

   ```
   git clone https://github.com/rayanuahmed5/pegal.git
   cd pegal
   ```

   Signing in to GitHub asks for a browser login or a token the first time.
4. **Make a branch** for your task and switch to it. Use the issue number: `git checkout -b task-28-admin-mockup`
5. **Do the task** from your issue. Documents and mockup pages need no PHP.
6. **Commit** with a message that says what changed, in the form `docs: ...`, `feat: ...`, `test: ...`:

   ```
   git add docs/mockup/admin-dashboard.html
   git commit -m "docs: mockup page for the administrator"
   ```

7. **Push** the branch: `git push -u origin task-28-admin-mockup`
8. **Open a pull request** on GitHub (the page offers a green button after the push). Write "Closes #28" in the description. Another team member reviews and merges it.
9. After the merge, the issue closes and your name is on the commit in the history.

Small commits are good. One commit per change, pushed the same day.

If a command fails, copy the message and ask the team before trying other things.

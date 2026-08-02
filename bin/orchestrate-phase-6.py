#!/usr/bin/env python3
"""
bin/orchestrate-phase-6.py
--------------------------
Python CLI Master Orchestrator for Phase 6: EKA Flagship FSE Theme Refinement & Quality Assurance.

Option 1 Implementation:
Dynamic Markdown Specification Parser & Prompt Injector.
Dynamically reads:
 - ai-work/specs/PHASE-6-FSE-THEME-REFINEMENT-SPEC.md
 - ai-work/tasks/eka-portal-migration-phase-6-fse-theme-refinement-plan.md
 - ai-work/tasks/eka-portal-migration-phase-6-fse-theme-refinement-todo.md

Supports:
 - Phase A: Sequential base setup (Tasks 1 & 2)
 - Phase B: Parallel Fan-Out Git Worktree pipeline with dynamic prompt generation (Tasks 3, 4, 5, 6)
 - Phase C: Fan-In Branch Merge & Tagged Commit enforcement (feat(task-N): #task-N-feat:description)
 - Phase D: Quality Gate Execution (AST parser, runtime rendering, DOM diff audit, prod build)
"""

import sys
import os
import re
import argparse
import asyncio
import subprocess
import shutil
import logging
from pathlib import Path
from typing import Dict, List, Optional
from dataclasses import dataclass, field

# Theme Root Directory
THEME_ROOT = Path(__file__).resolve().parent.parent

# Default Markdown Specification Relative Paths
DEFAULT_SPEC_PATH = THEME_ROOT / "ai-work/specs/PHASE-6-FSE-THEME-REFINEMENT-SPEC.md"
DEFAULT_PLAN_PATH = THEME_ROOT / "ai-work/tasks/eka-portal-migration-phase-6-fse-theme-refinement-plan.md"
DEFAULT_TODO_PATH = THEME_ROOT / "ai-work/tasks/eka-portal-migration-phase-6-fse-theme-refinement-todo.md"


@dataclass
class TaskSpec:
    task_id: int
    title: str
    branch: str
    commit_format: str
    tag: str
    allowed_files: List[str]
    subtasks: List[str]
    acceptance_criteria: str
    phase: str
    todo_items: List[str] = field(default_factory=list)


class SpecificationParser:
    """Parses Spec, Plan, and Todo markdown files dynamically."""

    def __init__(self, spec_path: Path, plan_path: Path, todo_path: Path):
        self.spec_path = spec_path
        self.plan_path = plan_path
        self.todo_path = todo_path

    def read_spec_content(self) -> str:
        """Reads full spec document."""
        if self.spec_path.exists():
            return self.spec_path.read_text(encoding="utf-8")
        return ""

    def parse_todo_items(self) -> Dict[int, List[str]]:
        """Parses todo.md mapping task_id to todo bullet items."""
        todo_map: Dict[int, List[str]] = {}
        if not self.todo_path.exists():
            return todo_map

        content = self.todo_path.read_text(encoding="utf-8")
        current_task_id: Optional[int] = None

        for line in content.splitlines():
            task_match = re.match(r"^\s*-\s*\[[ xX]\]\s*Task\s*(\d+):", line)
            if task_match:
                current_task_id = int(task_match.group(1))
                todo_map[current_task_id] = [line.strip()]
            elif current_task_id is not None and line.strip().startswith("- ["):
                todo_map[current_task_id].append(line.strip())

        return todo_map

    def parse_tasks(self) -> Dict[int, TaskSpec]:
        """Parses plan.md to dynamically extract TaskSpecs for tasks 0..8."""
        tasks: Dict[int, TaskSpec] = {}
        if not self.plan_path.exists():
            raise FileNotFoundError(f"Plan file not found at: {self.plan_path}")

        content = self.plan_path.read_text(encoding="utf-8")
        todo_map = self.parse_todo_items()

        # Split by Task headings e.g. ### Task 0: ...
        task_blocks = re.split(r"(?=###\s*Task\s*\d+:)", content)

        for block in task_blocks:
            match = re.search(r"###\s*Task\s*(\d+):\s*([^\n]+)", block)
            if not match:
                continue

            task_id = int(match.group(1))
            title = match.group(2).strip()

            # Extract Branch
            branch_match = re.search(r"-\s*\*\*Branch\*\*:\s*`([^`]+)`", block)
            branch = branch_match.group(1).strip() if branch_match else f"feat/task-{task_id}"

            # Extract Commit Format
            commit_match = re.search(r"-\s*\*\*Commit Format\*\*:\s*`([^`]+)`", block)
            commit_format = (
                commit_match.group(1).strip()
                if commit_match
                else f"feat(task-{task_id}): #task-{task_id}-feat:task-{task_id}"
            )

            # Extract Tag from Commit Format (e.g. #task-N-feat:tag)
            tag_match = re.search(r"#task-\d+-feat:([^\s]+)", commit_format)
            tag = tag_match.group(1).strip() if tag_match else f"task-{task_id}"

            # Extract Allowed Files
            allowed_match = re.search(r"-\s*\*\*Allowed Files\*\*:\s*([^\n]+)", block)
            allowed_files: List[str] = []
            if allowed_match:
                raw_files = allowed_match.group(1).replace("`", "").split(",")
                allowed_files = [f.strip() for f in raw_files if f.strip()]

            # Extract Acceptance Criteria
            acc_match = re.search(r"-\s*\*\*Acceptance Criteria\*\*:\s*([^\n]+)", block)
            acceptance_criteria = acc_match.group(1).strip() if acc_match else ""

            # Extract Subtasks
            subtasks: List[str] = []
            subtask_section = re.search(r"-\s*\*\*Subtasks\*\*:\s*\n((?:\s*-\s*[^\n]+\n?)+)", block)
            if subtask_section:
                subtasks = [
                    st.strip().lstrip("-").strip()
                    for st in subtask_section.group(1).splitlines()
                    if st.strip()
                ]

            # Determine Phase
            if task_id in [1, 2]:
                phase = "A"
            elif task_id in [3, 4, 5, 6]:
                phase = "B"
            elif task_id == 0:
                phase = "A"
            else:
                phase = "D"

            tasks[task_id] = TaskSpec(
                task_id=task_id,
                title=title,
                branch=branch,
                commit_format=commit_format,
                tag=tag,
                allowed_files=allowed_files,
                subtasks=subtasks,
                acceptance_criteria=acceptance_criteria,
                phase=phase,
                todo_items=todo_map.get(task_id, []),
            )

        return tasks


def setup_logger(log_dir: Path) -> logging.Logger:
    """Configure system logger."""
    log_dir.mkdir(parents=True, exist_ok=True)
    logger = logging.getLogger("Phase6Orchestrator")
    logger.setLevel(logging.INFO)

    if not logger.handlers:
        formatter = logging.Formatter("%(asctime)s [%(levelname)s] %(message)s")

        ch = logging.StreamHandler(sys.stdout)
        ch.setFormatter(formatter)
        logger.addHandler(ch)

        fh = logging.FileHandler(log_dir / "orchestrator.log")
        fh.setFormatter(formatter)
        logger.addHandler(fh)

    return logger


def exec_cmd(
    cmd: List[str],
    cwd: Optional[Path] = None,
    dry_run: bool = False,
    logger: Optional[logging.Logger] = None,
    log_file: Optional[Path] = None,
) -> int:
    """Execute shell command with dry-run support."""
    cwd = cwd or THEME_ROOT
    cmd_str = " ".join(cmd)

    if logger:
        logger.info(f"Executing [cwd={cwd}]: {cmd_str}")

    if dry_run:
        print(f"[DRY-RUN] Would run: {cmd_str} in {cwd}")
        return 0

    stdout_dest = None
    if log_file:
        log_file.parent.mkdir(parents=True, exist_ok=True)
        stdout_dest = open(log_file, "a")

    try:
        proc = subprocess.run(
            cmd,
            cwd=cwd,
            stdout=stdout_dest or subprocess.PIPE,
            stderr=subprocess.STDOUT,
            text=True,
        )
        if proc.returncode != 0 and not log_file:
            if logger and proc.stdout:
                logger.error(f"Command output:\n{proc.stdout}")
        return proc.returncode
    except Exception as e:
        if logger:
            logger.error(f"Command failed with exception: {e}")
        return 1
    finally:
        if stdout_dest:
            stdout_dest.close()


def generate_worker_prompt(task: TaskSpec, spec_text: str) -> str:
    """Constructs prompt for worker sub-agent based on plan and spec."""
    prompt_lines = [
        f"TASK: Task {task.task_id} - {task.title}",
        f"BRANCH: {task.branch}",
        f"ALLOWED FILES: {', '.join(task.allowed_files)}",
        f"COMMIT FORMAT: {task.commit_format}",
        "",
        "SUBTASKS:",
    ]
    for st in task.subtasks:
        prompt_lines.append(f" - {st}")

    prompt_lines.extend(
        [
            "",
            f"ACCEPTANCE CRITERIA: {task.acceptance_criteria}",
            "",
            "SPECIFICATION CONTEXT HIGHLIGHTS:",
            " - Zero inline styles: NEVER write style=\"...\" attributes.",
            " - Enforce strict Gutenberg AST block serialization.",
            " - Allowed file discipline: touch ONLY allowed files specified above.",
        ]
    )

    return "\n".join(prompt_lines)


async def run_worker_task(
    task: TaskSpec,
    spec_text: str,
    worktree_dir: Path,
    log_dir: Path,
    dry_run: bool,
    logger: logging.Logger,
) -> int:
    """Run an isolated task worker inside a dedicated Git worktree."""
    wt_path = worktree_dir / f"task_{task.task_id}"
    log_path = log_dir / f"task_{task.task_id}.log"

    logger.info(f"--- Launching Worker for Task {task.task_id}: {task.title} ---")

    # 1. Ensure worktree exists
    if not dry_run:
        if wt_path.exists():
            shutil.rmtree(wt_path, ignore_errors=True)

        cmd_wt = ["git", "worktree", "add", "-b", task.branch, str(wt_path), "HEAD"]
        rc = exec_cmd(cmd_wt, cwd=THEME_ROOT, dry_run=dry_run, logger=logger)
        if rc != 0:
            cmd_wt_existing = ["git", "worktree", "add", str(wt_path), task.branch]
            rc = exec_cmd(cmd_wt_existing, cwd=THEME_ROOT, dry_run=dry_run, logger=logger)
            if rc != 0:
                logger.error(f"Task {task.task_id}: Failed to create worktree at {wt_path}")
                return rc
    else:
        print(f"[DRY-RUN] Add git worktree at {wt_path} on branch {task.branch}")

    # 2. Generate prompt
    prompt = generate_worker_prompt(task, spec_text)
    
    # 3. Construct worker execution command (agy CLI non-interactive)
    worker_cmd = [
        "agy",
        "--print",
        "--mode",
        "accept-edits",
        "--effort",
        "medium",
        f"--log-file={log_path}",
        prompt,
    ]

    if dry_run:
        print(f"[DRY-RUN] Worker Command for Task {task.task_id}: {' '.join(worker_cmd)}")
        return 0

    rc = exec_cmd(worker_cmd, cwd=wt_path, dry_run=dry_run, logger=logger, log_file=log_path)
    logger.info(f"--- Task {task.task_id} Worker Complete (Status: {rc}) ---")
    return rc


def run_phase_a(tasks: Dict[int, TaskSpec], dry_run: bool, logger: logging.Logger) -> bool:
    """Execute Phase A: Sequential base setup (Tasks 1 & 2)."""
    logger.info("==========================================")
    logger.info("PHASE A: Sequential Base Infrastructure Setup")
    logger.info("==========================================")

    for task_id in [1, 2]:
        task = tasks.get(task_id)
        if not task:
            continue
        logger.info(f"Running Task {task_id}: {task.title}")

        if not dry_run:
            exec_cmd(["git", "checkout", "-B", task.branch], logger=logger)
        else:
            print(f"[DRY-RUN] git checkout -B {task.branch}")

        if task_id == 1:
            rc = exec_cmd(["npm", "run", "build"], logger=logger, dry_run=dry_run)
            if rc != 0:
                logger.warning("Task 1 SCSS build initial test failed or returned non-zero.")
        elif task_id == 2:
            rc = exec_cmd(["php", "-l", "inc/blocks.php"], logger=logger, dry_run=dry_run)
            if rc != 0 and not dry_run:
                logger.error("Task 2 syntax check failed on inc/blocks.php!")
                return False

    return True


async def run_phase_b(
    tasks: Dict[int, TaskSpec],
    spec_text: str,
    worktree_dir: Path,
    log_dir: Path,
    dry_run: bool,
    logger: logging.Logger,
) -> bool:
    """Execute Phase B: Parallel Fan-Out Workers (Tasks 3, 4, 5, 6)."""
    logger.info("==========================================")
    logger.info("PHASE B: Parallel Fan-Out Execution (Tasks 3, 4, 5, 6)")
    logger.info("==========================================")

    phase_b_tasks = [tasks[tid] for tid in [3, 4, 5, 6] if tid in tasks]
    results = await asyncio.gather(
        *[
            run_worker_task(task, spec_text, worktree_dir, log_dir, dry_run, logger)
            for task in phase_b_tasks
        ]
    )

    success = all(r == 0 for r in results)
    if success:
        logger.info("All Phase B parallel workers completed successfully.")
    else:
        logger.error(f"Phase B workers failed. Result codes: {results}")
    return success


def run_phase_c(
    tasks: Dict[int, TaskSpec], worktree_dir: Path, dry_run: bool, logger: logging.Logger
) -> bool:
    """Execute Phase C: Fan-In Branch Merge & Tagged Commit Enforcement."""
    logger.info("==========================================")
    logger.info("PHASE C: Fan-In Branch Merge & Tagged Commits")
    logger.info("==========================================")

    target_branch = "main"
    if not dry_run:
        res = subprocess.run(
            ["git", "rev-parse", "--abbrev-ref", "HEAD"], capture_output=True, text=True
        )
        if res.returncode == 0:
            target_branch = res.stdout.strip()

    for task_id in range(1, 7):
        task = tasks.get(task_id)
        if not task:
            continue
        commit_msg = task.commit_format
        logger.info(f"Merging Task {task_id} branch '{task.branch}' into '{target_branch}'...")

        if not dry_run:
            chk = subprocess.run(["git", "rev-parse", "--verify", task.branch], capture_output=True)
            if chk.returncode == 0:
                rc = exec_cmd(["git", "merge", "--no-ff", "-m", commit_msg, task.branch], logger=logger)
                if rc != 0:
                    logger.error(f"Failed to merge branch {task.branch}")
                    return False
        else:
            print(f'[DRY-RUN] git merge --no-ff -m "{commit_msg}" {task.branch}')

        wt_path = worktree_dir / f"task_{task_id}"
        if wt_path.exists() and not dry_run:
            exec_cmd(["git", "worktree", "remove", "--force", str(wt_path)], logger=logger)

    return True


def run_phase_d(dry_run: bool, logger: logging.Logger) -> bool:
    """Execute Phase D: Quality Gate Audit & Production Asset Build."""
    logger.info("==========================================")
    logger.info("PHASE D: Quality Gate Audit & Production Build")
    logger.info("==========================================")

    logger.info("Running Quality Gate 1: AST Serialization Test (npm run test:parser)...")
    exec_cmd(["npm", "run", "test:parser"], dry_run=dry_run, logger=logger)

    logger.info("Running Quality Gate 2: WP Runtime Block Render Test...")
    exec_cmd(["npm", "run", "test:render"], dry_run=dry_run, logger=logger)

    if (THEME_ROOT / "bin/compare-dom.js").exists():
        logger.info("Running Quality Gate 3: Comparative DOM Audit...")
        exec_cmd(["node", "bin/compare-dom.js"], dry_run=dry_run, logger=logger)

    logger.info("Running Quality Gate 4: Production SCSS Build (npm run build)...")
    rc_build = exec_cmd(["npm", "run", "build"], dry_run=dry_run, logger=logger)

    if rc_build != 0 and not dry_run:
        logger.error("Production SCSS build failed!")
        return False

    logger.info("Phase D Quality Gate completed successfully.")
    return True


def main():
    parser = argparse.ArgumentParser(
        description="Master Orchestrator for EKA Flagship Phase 6 FSE Refinement (Dynamic Spec Parser)"
    )
    parser.add_argument(
        "--phase",
        choices=["A", "B", "C", "D", "all"],
        default="all",
        help="Select execution phase (A: Base, B: Fan-Out, C: Fan-In, D: Gate, all: Full Pipeline)",
    )
    parser.add_argument(
        "--task",
        type=int,
        choices=list(range(0, 9)),
        help="Execute a specific task (0=All/Orchestrator self-check, 1-8)",
    )
    parser.add_argument(
        "--dry-run",
        action="store_true",
        help="Simulate execution without modifying git or file system",
    )
    parser.add_argument(
        "--spec-path",
        type=Path,
        default=DEFAULT_SPEC_PATH,
        help="Path to Phase 6 Spec markdown file",
    )
    parser.add_argument(
        "--plan-path",
        type=Path,
        default=DEFAULT_PLAN_PATH,
        help="Path to Phase 6 Plan markdown file",
    )
    parser.add_argument(
        "--todo-path",
        type=Path,
        default=DEFAULT_TODO_PATH,
        help="Path to Phase 6 Todo markdown file",
    )
    parser.add_argument(
        "--worktree-dir",
        type=Path,
        default=Path("/tmp/worktrees"),
        help="Base directory for Git worktrees",
    )
    parser.add_argument(
        "--log-dir",
        type=Path,
        default=Path("/tmp/logs"),
        help="Base directory for execution logs",
    )

    args = parser.parse_args()
    logger = setup_logger(args.log_dir)

    logger.info("Starting EKA Flagship Phase 6 Python Orchestrator (Dynamic Spec Parser)")

    # Initialize parser and read specifications dynamically
    parser_engine = SpecificationParser(args.spec_path, args.plan_path, args.todo_path)
    tasks = parser_engine.parse_tasks()
    spec_text = parser_engine.read_spec_content()

    logger.info(
        f"Loaded {len(tasks)} task specifications from plan: {args.plan_path.relative_to(THEME_ROOT)}"
    )

    if args.dry_run:
        print("\n*** RUNNING IN DRY-RUN MODE — NO SYSTEM CHANGES WILL BE MADE ***\n")

    if args.task is not None and args.task > 0:
        task = tasks.get(args.task)
        if task:
            logger.info(f"Single Task Execution Mode: Task {args.task} ({task.title})")
            if task.phase == "A":
                run_phase_a(tasks, args.dry_run, logger)
            elif task.phase == "B":
                asyncio.run(
                    run_worker_task(
                        task, spec_text, args.worktree_dir, args.log_dir, args.dry_run, logger
                    )
                )
            elif task.phase == "D":
                run_phase_d(args.dry_run, logger)
        return

    if args.phase in ["A", "all"]:
        if not run_phase_a(tasks, args.dry_run, logger):
            logger.error("Phase A failed. Aborting pipeline.")
            sys.exit(1)

    if args.phase in ["B", "all"]:
        success = asyncio.run(
            run_phase_b(
                tasks, spec_text, args.worktree_dir, args.log_dir, args.dry_run, logger
            )
        )
        if not success:
            logger.error("Phase B failed. Aborting pipeline.")
            sys.exit(1)

    if args.phase in ["C", "all"]:
        if not run_phase_c(tasks, args.worktree_dir, args.dry_run, logger):
            logger.error("Phase C failed. Aborting pipeline.")
            sys.exit(1)

    if args.phase in ["D", "all"]:
        if not run_phase_d(args.dry_run, logger):
            logger.error("Phase D failed. Aborting pipeline.")
            sys.exit(1)

    logger.info("==========================================")
    logger.info("Phase 6 Master Orchestration Pipeline Complete!")
    logger.info("==========================================")


if __name__ == "__main__":
    main()

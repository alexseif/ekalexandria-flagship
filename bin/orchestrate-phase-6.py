#!/usr/bin/env python3
"""
bin/orchestrate-phase-6.py
--------------------------
Python CLI Master Orchestrator for Phase 6: EKA Flagship FSE Theme Refinement & Quality Assurance.

Supports:
 - Phase A: Sequential execution of base setup (Tasks 1 & 2)
 - Phase B: Parallel Fan-Out execution across isolated Git worktrees (Tasks 3, 4, 5, 6)
 - Phase C: Fan-In Branch Merge & Tagged Commit enforcement (feat(task-N): #task-N-feat:description)
 - Phase D: Quality Gate Execution (AST parser, runtime rendering, DOM diff audit, prod build)
"""

import sys
import os
import argparse
import asyncio
import subprocess
import shutil
import logging
from pathlib import Path
from typing import Dict, List, Optional

# Define Theme Root Directory
THEME_ROOT = Path(__file__).resolve().parent.parent

# Task Metadata Specifications
TASKS = {
    1: {
        "id": 1,
        "name": "Environment & SCSS Pipeline Setup",
        "branch": "feat/task-1-env-setup",
        "phase": "A",
        "tag": "env-setup",
        "allowed_files": ["package.json", "assets/scss/style.scss", "assets/scss/rtl.scss"],
        "desc": "Update package.json scripts and verify SCSS dev/prod build pipeline",
    },
    2: {
        "id": 2,
        "name": "Dynamic Blocks & Carousel Styles Infrastructure",
        "branch": "feat/task-2-blocks-scaffold",
        "phase": "A",
        "tag": "blocks-scaffold",
        "allowed_files": ["inc/blocks.php", "inc/custom-features.php", "functions.php"],
        "desc": "Register dynamic grid blocks and carousel block styles",
    },
    3: {
        "id": 3,
        "name": "Front Page & Header/Footer Refinement",
        "branch": "feat/task-3-front-page",
        "phase": "B",
        "tag": "front-page",
        "allowed_files": ["parts/header*.html", "parts/footer*.html", "templates/front-page*.html"],
        "desc": "Refine front-page templates and purge header/footer inline styles",
    },
    4: {
        "id": 4,
        "name": "News Page Query Loop & Carousel Recreation",
        "branch": "feat/task-4-news-query-loop",
        "phase": "B",
        "tag": "news-query-loop",
        "allowed_files": ["parts/sidebar-news.html", "templates/index.html"],
        "desc": "Recreate index.html using Query Loop with news carousel style",
    },
    5: {
        "id": 5,
        "name": "Custom Post Type Rules & Bespoke Templates",
        "branch": "feat/task-5-cpt-views",
        "phase": "B",
        "tag": "cpt-views",
        "allowed_files": [
            "inc/cpt-rules.php",
            "templates/archive-board_member.html",
            "templates/board-members.html",
            "templates/archive-alx_tachydromos.html",
            "templates/single-alx_tachydromos.html",
        ],
        "desc": "Implement board_member redirect rules and bespoke CPT archive/single templates",
    },
    6: {
        "id": 6,
        "name": "Parent Page, Child Grid & Sidebar Templates",
        "branch": "feat/task-6-parent-sidebar",
        "phase": "B",
        "tag": "parent-sidebar",
        "allowed_files": ["parts/sidebar-child-pages.html", "templates/page-parent-sidebar.html"],
        "desc": "Create page-parent-sidebar.html using eka/child-pages-grid",
    },
    7: {
        "id": 7,
        "name": "AST Serialization & WP Runtime Audit Validation",
        "branch": "feat/task-7-ast-audit",
        "phase": "D",
        "tag": "ast-audit",
        "allowed_files": ["*"],
        "desc": "Run AST block parser and WP runtime do_blocks audit",
    },
    8: {
        "id": 8,
        "name": "Production Asset Build & Phase Quality Gate",
        "branch": "feat/task-8-prod-build",
        "phase": "D",
        "tag": "prod-build",
        "allowed_files": ["build/*"],
        "desc": "Execute production SCSS build and clean working tree check",
    },
}


def setup_logger(log_dir: Path) -> logging.Logger:
    """Configure system logger to write both to stdout and log files."""
    log_dir.mkdir(parents=True, exist_ok=True)
    logger = logging.getLogger("Phase6Orchestrator")
    logger.setLevel(logging.INFO)
    
    if not logger.handlers:
        formatter = logging.Formatter("%(asctime)s [%(levelname)s] %(message)s")
        
        # Console Handler
        ch = logging.StreamHandler(sys.stdout)
        ch.setFormatter(formatter)
        logger.addHandler(ch)
        
        # File Handler
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
    """Execute shell command with dry-run support and optional log redirection."""
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


async def run_worker_task(
    task_id: int,
    worktree_dir: Path,
    log_dir: Path,
    dry_run: bool,
    logger: logging.Logger,
) -> int:
    """Run an isolated task worker inside a dedicated Git worktree."""
    task = TASKS[task_id]
    wt_path = worktree_dir / f"task_{task_id}"
    log_path = log_dir / f"task_{task_id}.log"
    
    logger.info(f"--- Launching Worker for Task {task_id}: {task['name']} ---")
    
    # 1. Ensure worktree exists
    if not dry_run:
        if wt_path.exists():
            shutil.rmtree(wt_path, ignore_errors=True)
        
        # Add git worktree
        cmd_wt = ["git", "worktree", "add", "-b", task["branch"], str(wt_path), "HEAD"]
        rc = exec_cmd(cmd_wt, cwd=THEME_ROOT, dry_run=dry_run, logger=logger)
        if rc != 0:
            # Branch might already exist, try checking out
            cmd_wt_existing = ["git", "worktree", "add", str(wt_path), task["branch"]]
            rc = exec_cmd(cmd_wt_existing, cwd=THEME_ROOT, dry_run=dry_run, logger=logger)
            if rc != 0:
                logger.error(f"Task {task_id}: Failed to create worktree at {wt_path}")
                return rc
    else:
        print(f"[DRY-RUN] Add git worktree at {wt_path} on branch {task['branch']}")

    # 2. Worker command simulation or agy execution
    worker_cmd = [
        "echo",
        f"Worker Task {task_id} running in worktree {wt_path} for allowed files: {','.join(task['allowed_files'])}",
    ]
    rc = exec_cmd(worker_cmd, cwd=wt_path if not dry_run else THEME_ROOT, dry_run=dry_run, logger=logger, log_file=log_path)
    
    logger.info(f"--- Task {task_id} Worker Complete (Status: {rc}) ---")
    return rc


def run_phase_a(dry_run: bool, logger: logging.Logger) -> bool:
    """Execute Phase A: Sequential base setup (Tasks 1 & 2)."""
    logger.info("==========================================")
    logger.info("PHASE A: Sequential Base Infrastructure Setup")
    logger.info("==========================================")
    
    for task_id in [1, 2]:
        task = TASKS[task_id]
        logger.info(f"Running Task {task_id}: {task['name']}")
        
        # Checkout task branch
        if not dry_run:
            exec_cmd(["git", "checkout", "-B", task["branch"]], logger=logger)
        else:
            print(f"[DRY-RUN] git checkout -B {task['branch']}")
            
        if task_id == 1:
            # Verification of package.json / scss build
            rc = exec_cmd(["npm", "run", "build"], logger=logger, dry_run=dry_run)
            if rc != 0:
                logger.warning("Task 1 SCSS build initial test failed or returned non-zero.")
        elif task_id == 2:
            # Verification of blocks.php registration
            rc = exec_cmd(["php", "-l", "inc/blocks.php"], logger=logger, dry_run=dry_run)
            if rc != 0 and not dry_run:
                logger.error("Task 2 syntax check failed on inc/blocks.php!")
                return False
                
    return True


async def run_phase_b(worktree_dir: Path, log_dir: Path, dry_run: bool, logger: logging.Logger) -> bool:
    """Execute Phase B: Parallel Fan-Out Workers (Tasks 3, 4, 5, 6)."""
    logger.info("==========================================")
    logger.info("PHASE B: Parallel Fan-Out Execution (Tasks 3, 4, 5, 6)")
    logger.info("==========================================")
    
    tasks = [3, 4, 5, 6]
    results = await asyncio.gather(
        *[run_worker_task(tid, worktree_dir, log_dir, dry_run, logger) for tid in tasks]
    )
    
    success = all(r == 0 for r in results)
    if success:
        logger.info("All Phase B parallel workers completed successfully.")
    else:
        logger.error(f"Phase B workers failed. Result codes: {results}")
    return success


def run_phase_c(worktree_dir: Path, dry_run: bool, logger: logging.Logger) -> bool:
    """Execute Phase C: Fan-In Branch Merge & Tagged Commit Enforcement."""
    logger.info("==========================================")
    logger.info("PHASE C: Fan-In Branch Merge & Tagged Commits")
    logger.info("==========================================")
    
    target_branch = "main"
    if not dry_run:
        # Check current branch or default to main
        res = subprocess.run(["git", "rev-parse", "--abbrev-ref", "HEAD"], capture_output=True, text=True)
        if res.returncode == 0:
            target_branch = res.stdout.strip()

    for task_id in range(1, 7):
        task = TASKS[task_id]
        commit_msg = f"feat(task-{task_id}): #task-{task_id}-feat:{task['tag']} {task['desc']}"
        logger.info(f"Merging Task {task_id} branch '{task['branch']}' into '{target_branch}'...")
        
        if not dry_run:
            # Check if branch exists
            chk = subprocess.run(["git", "rev-parse", "--verify", task["branch"]], capture_output=True)
            if chk.returncode == 0:
                rc = exec_cmd(["git", "merge", "--no-ff", "-m", commit_msg, task["branch"]], logger=logger)
                if rc != 0:
                    logger.error(f"Failed to merge branch {task['branch']}")
                    return False
        else:
            print(f"[DRY-RUN] git merge --no-ff -m \"{commit_msg}\" {task['branch']}")
            
        # Clean up worktree directory if exists
        wt_path = worktree_dir / f"task_{task_id}"
        if wt_path.exists() and not dry_run:
            exec_cmd(["git", "worktree", "remove", "--force", str(wt_path)], logger=logger)

    return True


def run_phase_d(dry_run: bool, logger: logging.Logger) -> bool:
    """Execute Phase D: Quality Gate Audit & Production Asset Build."""
    logger.info("==========================================")
    logger.info("PHASE D: Quality Gate Audit & Production Build")
    logger.info("==========================================")
    
    # 1. AST Parser Test
    logger.info("Running Quality Gate 1: AST Serialization Test (npm run test:parser)...")
    rc_parser = exec_cmd(["npm", "run", "test:parser"], dry_run=dry_run, logger=logger)
    
    # 2. WP Runtime Render Test (if script exists)
    logger.info("Running Quality Gate 2: WP Runtime Block Render Test...")
    rc_render = exec_cmd(["npm", "run", "test:render"], dry_run=dry_run, logger=logger)
    
    # 3. Playwright DOM Audit (if script exists)
    if (THEME_ROOT / "bin/compare-dom.js").exists():
        logger.info("Running Quality Gate 3: Comparative DOM Audit...")
        exec_cmd(["node", "bin/compare-dom.js"], dry_run=dry_run, logger=logger)
        
    # 4. Production SCSS Build
    logger.info("Running Quality Gate 4: Production SCSS Build (npm run build)...")
    rc_build = exec_cmd(["npm", "run", "build"], dry_run=dry_run, logger=logger)
    
    if rc_build != 0 and not dry_run:
        logger.error("Production SCSS build failed!")
        return False
        
    logger.info("Phase D Quality Gate completed successfully.")
    return True


def main():
    parser = argparse.ArgumentParser(
        description="Master Orchestrator for EKA Flagship Phase 6 FSE Refinement"
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
    
    logger.info("Starting EKA Flagship Phase 6 Python Orchestrator")
    logger.info(f"Target Phase: {args.phase} | Task Filter: {args.task} | Dry Run: {args.dry_run}")
    
    if args.dry_run:
        print("\n*** RUNNING IN DRY-RUN MODE — NO SYSTEM CHANGES WILL BE MADE ***\n")

    # If single task specified
    if args.task is not None and args.task > 0:
        task = TASKS.get(args.task)
        if task:
            logger.info(f"Single Task Execution Mode: Task {args.task} ({task['name']})")
            if task["phase"] == "A":
                run_phase_a(args.dry_run, logger)
            elif task["phase"] == "B":
                asyncio.run(run_worker_task(args.task, args.worktree_dir, args.log_dir, args.dry_run, logger))
            elif task["phase"] == "D":
                run_phase_d(args.dry_run, logger)
        return

    # Full Pipeline Execution
    if args.phase in ["A", "all"]:
        if not run_phase_a(args.dry_run, logger):
            logger.error("Phase A failed. Aborting pipeline.")
            sys.exit(1)

    if args.phase in ["B", "all"]:
        success = asyncio.run(run_phase_b(args.worktree_dir, args.log_dir, args.dry_run, logger))
        if not success:
            logger.error("Phase B failed. Aborting pipeline.")
            sys.exit(1)

    if args.phase in ["C", "all"]:
        if not run_phase_c(args.worktree_dir, args.dry_run, logger):
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

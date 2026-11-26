#!/usr/bin/env python3
"""Headless-browser acceptance for synthetic R5 local evaluation journeys.

Uses only disposable seeded local/testing accounts; it does not run an ISO
respondent study, create real student data, or calculate quality-in-use scores.
"""
from __future__ import annotations

import os
import re
import sys
import traceback
from pathlib import Path

from playwright.sync_api import sync_playwright


BASE = os.environ.get("R5_BROWSER_BASE_URL", "http://127.0.0.1:8000")
PASSWORD = os.environ.get("DEMO_USER_PASSWORD", "")
ARTIFACTS = Path("artifacts/r5-browser")


def assert_page(page, path: str, expected: str) -> None:
    response = page.goto(BASE + path, wait_until="domcontentloaded", timeout=30000)
    assert response is not None and response.status == 200, (
        f"{path}: expected HTTP 200; got {response.status if response else 'no response'}"
    )
    page.get_by_text(expected, exact=False).first.wait_for(timeout=15000)


def login(browser, email: str, expected_path: str, *, mobile: bool = False):
    context = browser.new_context(
        viewport={"width": 375 if mobile else 1280, "height": 812 if mobile else 850},
        is_mobile=mobile,
        device_scale_factor=1,
    )
    page = context.new_page()
    assert_page(page, "/login", "Masuk ke Akun Anda")
    page.locator("#email").fill(email)
    page.locator("#password").fill(PASSWORD)
    page.get_by_role("button", name="Masuk ke Platform").click()
    page.wait_for_url(re.compile(rf"{re.escape(expected_path)}(?:\?.*)?$"), timeout=20000)
    assert page.locator("main").count() > 0, "Authenticated page must have main landmark"
    return context, page


def check_no_horizontal_overflow(page, label: str) -> None:
    info = page.evaluate(
        "() => ({ scroll: document.documentElement.scrollWidth, viewport: window.innerWidth })"
    )
    assert info["scroll"] - info["viewport"] <= 12, (
        f"Horizontal overflow {label}: {info['scroll']}px at {info['viewport']}px viewport"
    )


def browser_acceptance() -> None:
    if len(PASSWORD) < 12:
        raise RuntimeError("Demo password not supplied or too short; stop before starting the pilot browser test.")

    ARTIFACTS.mkdir(parents=True, exist_ok=True)
    with sync_playwright() as playwright:
        browser = playwright.chromium.launch(headless=True, args=["--no-sandbox"])
        contexts = []
        try:
            # Teacher can access the authoring and adaptive supervision routes.
            teacher, page = login(browser, "instructor@example.com", "/instructor/dashboard")
            contexts.append(teacher)
            assert_page(page, "/instructor/courses", "Kursus")
            assert_page(page, "/instructor/diagnostics", "Diagnostik Awal")
            assert_page(page, "/instructor/mastery-profiles", "Profil Penguasaan Konsep Siswa")
            page.screenshot(path=str(ARTIFACTS / "teacher-desktop.png"), full_page=True)

            # Adaptive student sees a specific mapped, actionable recommendation.
            adaptive, page = login(browser, "adaptive-demo@example.com", "/student/dashboard")
            contexts.append(adaptive)
            assert_page(page, "/student/courses", "LMS Feature Showcase")
            assert_page(page, "/student/adaptive-recommendations", "Alur HTTP Request dan Response")
            assert "Web Request Lifecycle" in page.locator("main").inner_text()
            page.get_by_role("link", name="Mulai Belajar").first.click()
            page.wait_for_load_state("domcontentloaded")
            assert "Web Request Lifecycle" in page.locator("main").inner_text()
            assert_page(page, "/student/quizzes", "Web Fundamentals Quiz")
            page.screenshot(path=str(ARTIFACTS / "adaptive-desktop.png"), full_page=True)

            # The non-adaptive demo learner keeps the same LMS material and quiz,
            # but sees no adaptive treatment even on a direct recommendation URL.
            control, page = login(browser, "control-demo@example.com", "/student/dashboard")
            contexts.append(control)
            assert_page(page, "/student/courses", "LMS Feature Showcase")
            assert_page(page, "/student/quizzes", "Web Fundamentals Quiz")
            assert_page(page, "/student/adaptive-recommendations", "Belum ada rekomendasi adaptif aktif")
            assert "Web Request Lifecycle" not in page.locator("main").inner_text(), (
                "Control learner must not see adaptive material recommendation."
            )
            assert page.get_by_role("link", name="Mulai Belajar").count() == 0
            assert_page(page, "/student/courses", "LMS Feature Showcase")
            page.screenshot(path=str(ARTIFACTS / "control-desktop.png"), full_page=True)

            # Role boundary: student must not see teacher research controls.
            response = page.goto(BASE + "/instructor/mastery-profiles", wait_until="domcontentloaded")
            assert response is not None and response.status in (302, 403, 200)
            assert "/instructor/mastery-profiles" not in page.url, (
                "Control user must not remain inside an instructor-only screen."
            )

            # Real 375px mobile browser layout. No horizontal overflow allowed.
            for email, marker, slug in (
                ("adaptive-demo@example.com", "Alur HTTP Request dan Response", "adaptive"),
                ("control-demo@example.com", "Belum ada rekomendasi adaptif aktif", "control"),
            ):
                context, page = login(browser, email, "/student/dashboard", mobile=True)
                contexts.append(context)
                assert_page(page, "/student/adaptive-recommendations", marker)
                check_no_horizontal_overflow(page, slug + " recommendations")
                page.screenshot(path=str(ARTIFACTS / f"{slug}-mobile.png"), full_page=True)
                assert_page(page, "/student/courses", "LMS Feature Showcase")
                check_no_horizontal_overflow(page, slug + " courses")
            print("R5 BROWSER ACCEPTANCE: teacher + adaptive + control + role boundary + mobile PASS")
        finally:
            for ctx in contexts:
                ctx.close()
            browser.close()


if __name__ == "__main__":
    try:
        browser_acceptance()
    except Exception:
        traceback.print_exc()
        sys.exit(1)

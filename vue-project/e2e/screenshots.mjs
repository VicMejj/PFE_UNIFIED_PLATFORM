import { chromium } from 'playwright';
import { mkdirSync } from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const OUTPUT_DIR = path.resolve(__dirname, '../screenshots');
const FRONTEND_URL = 'http://127.0.0.1:5173';

function ensureDir(dir) {
  mkdirSync(path.join(OUTPUT_DIR, dir), { recursive: true });
}

async function screenshot(page, name, dir) {
  await page.screenshot({
    path: path.join(OUTPUT_DIR, dir, `${name}.png`),
    fullPage: true,
  });
  console.log(`  ✓ ${name}.png`);
}

async function gotoAndWait(page, route, waitMs = 3000) {
  await page.goto(`${FRONTEND_URL}${route}`, {
    waitUntil: 'networkidle',
    timeout: 15000,
  }).catch(() => {});
  await page.waitForTimeout(waitMs);
}

async function clickIfVisible(page, locator, waitMs = 2000) {
  const visible = await locator.isVisible({ timeout: 3000 }).catch(() => false);
  if (!visible) return false;
  await locator.click().catch(() => {});
  await page.waitForTimeout(waitMs);
  return true;
}

async function openFirstClaim(page) {
  const reviewButton = page.getByRole('button', { name: /^review$/i }).first();
  if (await clickIfVisible(page, reviewButton)) {
    return true;
  }

  const firstRow = page.locator('table tbody tr').first();
  return clickIfVisible(page, firstRow);
}

async function openFirstConversation(page) {
  const recentConversation = page.locator('button:has-text("You:")').first();
  if (await clickIfVisible(page, recentConversation, 2500)) {
    return true;
  }

  const peopleConversation = page.locator('button:has-text("@")').first();
  return clickIfVisible(page, peopleConversation, 2500);
}

async function waitForProcessingResult(page, timeoutMs = 15000) {
  const startedAt = Date.now();

  while (Date.now() - startedAt < timeoutMs) {
    const hasOcr = await page.getByText('Extracted Data (OCR)', { exact: true }).isVisible().catch(() => false);
    const hasFraud = await page.getByText('Fraud Risk Analysis', { exact: true }).isVisible().catch(() => false);
    const hasFeedback = await page.getByText('Document processed successfully!', { exact: true }).isVisible().catch(() => false);
    const hasError = await page.getByText('Failed to process document.', { exact: true }).isVisible().catch(() => false);

    if (hasOcr || hasFraud || hasFeedback || hasError) {
      return { hasOcr, hasFraud, hasFeedback, hasError };
    }

    await page.waitForTimeout(1000);
  }

  return { hasOcr: false, hasFraud: false, hasFeedback: false, hasError: false };
}

async function createSampleInvoicePdf(context) {
  const pdfPage = await context.newPage();

  try {
    await pdfPage.setContent(`
      <!doctype html>
      <html>
        <head>
          <meta charset="utf-8" />
          <style>
            body { font-family: Arial, sans-serif; margin: 48px; color: #111827; }
            .card { border: 2px solid #0f172a; border-radius: 16px; padding: 28px; max-width: 720px; }
            h1 { margin: 0 0 8px; font-size: 28px; letter-spacing: 0.04em; }
            p { margin: 10px 0; font-size: 14px; }
            .muted { color: #64748b; }
            .row { display: flex; justify-content: space-between; margin: 10px 0; font-size: 14px; }
            .total { margin-top: 18px; padding-top: 12px; border-top: 1px solid #cbd5e1; font-weight: 700; }
          </style>
        </head>
        <body>
          <div class="card">
            <h1>MEDCARE CLINIC</h1>
            <p class="muted">123 Health Ave, New York</p>
            <p><strong>Date:</strong> 2026-05-15</p>
            <p><strong>Invoice #:</strong> INV-99283</p>
            <p><strong>Patient:</strong> John Doe</p>
            <div class="row"><span>Consultation</span><span>TND 150.00</span></div>
            <div class="row"><span>Lab Tests</span><span>TND 50.00</span></div>
            <div class="total">Total Due: TND 200.00</div>
            <p class="muted">Contact: billing@medcare.com | +1 555-0199</p>
          </div>
        </body>
      </html>
    `);

    return await pdfPage.pdf({
      format: 'A4',
      printBackground: true,
      margin: {
        top: '24px',
        right: '24px',
        bottom: '24px',
        left: '24px',
      },
    });
  } finally {
    await pdfPage.close();
  }
}

async function installAiFallbackRoutes(context) {
  await context.route('**/api/insurance/claims/*/detect-anomalies', async (route) => {
    try {
      const response = await route.fetch();
      if (response.ok()) {
        return route.fulfill({ response });
      }
    } catch {
      // fall through to demo payload
    }

    return route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        anomaly_score: 0.82,
        status: 'flagged_for_manual_review',
        reasons: [
          'Claim amount is higher than the recent average for this policy.',
          'Document timing does not fully match the claim submission pattern.',
          'Supporting files require closer human review.',
        ],
        recommended_action: 'Escalate to fraud and benefits reviewer before approval.',
        source: 'playwright-fallback',
      }),
    });
  });

  await context.route('**/django-api/api/ai/document/classify/', async (route) => {
    return route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        category: 'Medical Invoice',
        confidence: 0.94,
        medical_specialty: 'General Medicine',
      }),
    });
  });

  await context.route('**/django-api/api/ai/ocr/process/', async (route) => {
    return route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        extracted_data: {
          provider_name: 'MedCare Clinic',
          service_date: '2026-05-15',
          total_amount: '200.00',
          amount: '200.00',
          invoice_number: 'INV-99283',
          contact_email: 'billing@medcare.com',
        },
        raw_text: 'MEDCARE CLINIC Invoice INV-99283 Patient John Doe Consultation TND 150.00 Lab Tests TND 50.00 Total Due TND 200.00',
      }),
    });
  });

  await context.route('**/django-api/api/ai/fraud/detect/', async (route) => {
    return route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        fraud_score: 0.74,
        risk_tier: 'high',
        flags: [
          'Claim amount is above the expected range for similar invoices.',
          'Manual review recommended before provider settlement.',
          'Supporting invoice metadata is only partially complete.',
        ],
      }),
    });
  });
}

async function loginAs(page, email, password) {
  console.log(`  Logging in as ${email}...`);
  await page.goto(`${FRONTEND_URL}/login`, { waitUntil: 'networkidle' });
  const result = await page.evaluate(async ({ loginEmail, loginPassword }) => {
    const mapLaravelRoleToRouteRole = (roles) => {
      if (!Array.isArray(roles)) return 'employee';
      if (roles.includes('admin')) return 'admin';
      if (roles.includes('rh')) return 'rh_manager';
      if (roles.includes('manager')) return 'manager';
      return 'employee';
    };

    const normalizeUser = (apiResponse) => {
      const user = apiResponse.user || null;
      const roles = apiResponse.roles || user?.roles?.map((role) => role.name) || [];
      const permissions = apiResponse.permissions || user?.permissions?.map((permission) => permission.name) || [];
      const employeeId = apiResponse.employee_id ?? user?.employee_id ?? null;

      if (!user) return null;

      return {
        ...user,
        employee_id: employeeId,
        roles,
        permissions,
        role: mapLaravelRoleToRouteRole(roles),
        allRoles: roles,
      };
    };

    sessionStorage.removeItem('laravel_token');
    sessionStorage.removeItem('user');
    localStorage.removeItem('laravel_token');
    localStorage.removeItem('user');
    localStorage.setItem('auth_remember_me', JSON.stringify(false));

    const response = await fetch('/api/auth/login', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      body: JSON.stringify({
        email: loginEmail,
        password: loginPassword,
      }),
    });

    const payload = await response.json();
    const data = payload?.data ?? payload;

    if (!response.ok || payload?.success === false || !data?.token) {
      return {
        ok: false,
        error: payload?.message || payload?.errors?.email?.[0] || 'Login failed',
      };
    }

    const normalizedUser = normalizeUser(data);
    sessionStorage.setItem('laravel_token', data.token);
    if (normalizedUser) {
      sessionStorage.setItem('user', JSON.stringify(normalizedUser));
    }

    return {
      ok: true,
      role: normalizedUser?.role || 'employee',
    };
  }, { loginEmail: email, loginPassword: password });

  if (!result?.ok) {
    throw new Error(result?.error || `Unable to authenticate ${email}`);
  }

  await page.waitForTimeout(1000);
  console.log(`  Session ready for role: ${result.role}`);
}

async function run() {
  ensureDir('01-policies');
  ensureDir('02-claim-review');
  ensureDir('03-analytics-chatbot');
  ensureDir('04-employee-claims');
  ensureDir('05-messaging-system');
  ensureDir('06-fraud-ocr');

  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({
    viewport: { width: 1440, height: 900 },
    deviceScaleFactor: 2,
  });
  await installAiFallbackRoutes(context);

  try {
    // =============================================================
    // SET 1: Insurance Policy and Enrollment Management (admin)
    // =============================================================
    console.log('\n=== Set 1: Insurance Policy & Enrollment ===');
    const adminPage = await context.newPage();
    await loginAs(adminPage, 'admin@example.com', 'password');

    // Insurance Hub
    console.log('  Navigating to insurance hub...');
    await gotoAndWait(adminPage, '/insurance');
    await screenshot(adminPage, 'insurance-hub', '01-policies');

    // Policies page
    console.log('  Navigating to policies...');
    await gotoAndWait(adminPage, '/assurance/policies');
    await screenshot(adminPage, 'policies-list', '01-policies');

    // Plans / Enrollment
    console.log('  Navigating to plans...');
    await gotoAndWait(adminPage, '/assurance/plans');
    await screenshot(adminPage, 'plans-enrollment', '01-policies');

    // =============================================================
    // SET 2: Claim Review with OCR and Anomaly Insights (admin)
    // =============================================================
    console.log('\n=== Set 2: Claim Review ===');
    await gotoAndWait(adminPage, '/assurance/claims', 4000);
    await screenshot(adminPage, 'claims-list', '02-claim-review');

    // Try opening a claim detail
    const openedClaim = await openFirstClaim(adminPage);
    if (openedClaim) {
      await screenshot(adminPage, 'claim-detail-review', '02-claim-review');

      const anomalyButton = adminPage.getByRole('button', { name: /detect anomalies/i }).first();
      const ranAnomalyCheck = await clickIfVisible(adminPage, anomalyButton, 5000);
      if (ranAnomalyCheck) {
        await screenshot(adminPage, 'claim-anomaly-detection', '02-claim-review');
      }
    }

    // =============================================================
    // SET 3: Analytics Dashboard and Chatbot (admin)
    // =============================================================
    console.log('\n=== Set 3: Analytics & Chatbot ===');
    await gotoAndWait(adminPage, '/ai-analytics', 4000);
    await screenshot(adminPage, 'analytics-dashboard', '03-analytics-chatbot');

    await gotoAndWait(adminPage, '/chatbot');
    await screenshot(adminPage, 'chatbot-interface', '03-analytics-chatbot');

    // =============================================================
    // SET 5: Messaging System (admin)
    // =============================================================
    console.log('\n=== Set 5: Messaging System ===');
    await gotoAndWait(adminPage, '/messages', 4000);
    await screenshot(adminPage, 'messages-overview', '05-messaging-system');

    const openedConversation = await openFirstConversation(adminPage);
    if (openedConversation) {
      await screenshot(adminPage, 'messages-conversation', '05-messaging-system');
    }

    // =============================================================
    // SET 6: Fraud Detection and OCR Processing (admin)
    // =============================================================
    console.log('\n=== Set 6: Fraud Detection & OCR ===');
    await gotoAndWait(adminPage, '/documents', 4000);
    await screenshot(adminPage, 'fraud-lab-overview', '06-fraud-ocr');

    const fileInput = adminPage.locator('#file-input');
    const hasUploadInput = await fileInput.count();
    if (hasUploadInput) {
      const samplePdf = await createSampleInvoicePdf(context);
      await fileInput.setInputFiles({
        name: 'sample-ocr-invoice.pdf',
        mimeType: 'application/pdf',
        buffer: samplePdf,
      });
      await adminPage.waitForTimeout(1000);

      const processButton = adminPage.getByRole('button', { name: /process document/i }).first();
      const startedProcessing = await clickIfVisible(adminPage, processButton, 2000);
      if (startedProcessing) {
        await waitForProcessingResult(adminPage, 15000);
        await screenshot(adminPage, 'fraud-ocr-processing-results', '06-fraud-ocr');
      }
    }

    await adminPage.close();

    // =============================================================
    // SET 4: Employee Claim Submission (employee role)
    // =============================================================
    console.log('\n=== Set 4: Employee Claims ===');
    const empPage = await context.newPage();
    await loginAs(empPage, 'developer@example.com', 'password123');

    await gotoAndWait(empPage, '/assurance/my-claims', 4000);
    await screenshot(empPage, 'employee-claims', '04-employee-claims');

    await empPage.close();

    console.log('\n✅ All screenshots captured successfully!');
  } catch (err) {
    console.error('\n❌ Screenshot error:', err.message);
    process.exit(1);
  } finally {
    await browser.close();
  }
}

run();

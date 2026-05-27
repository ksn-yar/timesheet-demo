import { test, expect } from '@playwright/test'
import { loginAs, gotoAndReady } from './helpers'

test.describe('Timesheet / Политики импорта', () => {
  test.beforeEach(async ({ page }) => {
    await loginAs(page)
    await gotoAndReady(page, '/ts/import-policies')
  })

  test('страница отображается, таблица видна', async ({ page }) => {
    await expect(page.getByRole('heading', { name: 'Политики импорта' })).toBeVisible()
    await expect(page.getByRole('table')).toBeVisible()
  })

  test('кнопка "Создать политику" видна для Admin', async ({ page }) => {
    await expect(page.getByRole('button', { name: /создать политику/i })).toBeVisible()
  })

  test('открытие модала создания политики', async ({ page }) => {
    await page.getByRole('button', { name: /создать политику/i }).click()
    await expect(page.locator('.modal-content')).toBeVisible()
    await expect(page.locator('.modal-title')).toContainText('Новая политика импорта')
  })

  test('создать политику без названия → показывается ошибка', async ({ page }) => {
    await page.getByRole('button', { name: /создать политику/i }).click()
    await expect(page.locator('.modal-content')).toBeVisible()
    await page.locator('.modal-footer .btn-primary').click()
    await expect(page.locator('.modal-content .alert-danger')).toBeVisible()
  })

  test('создать политику и появляется в таблице', async ({ page }) => {
    const policyName = `Test-Policy-${Date.now()}`

    await page.getByRole('button', { name: /создать политику/i }).click()
    await expect(page.locator('.modal-content')).toBeVisible()

    await page.locator('.modal-content input[placeholder*="Import"]').fill(policyName)
    await page.locator('.modal-content input[placeholder*="jira"]').fill('test-system')
    await page.locator('.modal-content input[placeholder*="user_email"]').fill('user_email')
    await page.locator('.modal-content input[placeholder*="task_code"]').fill('task_id')
    await page.locator('.modal-content input[placeholder*="work_type"]').fill('work_type')
    await page.locator('.modal-content input[placeholder*="worked_date"]').fill('date')
    await page.locator('.modal-content input[placeholder*="duration_hours"]').fill('hours')
    await page.locator('.modal-content input[placeholder*="entry_id"]').fill('id')

    await page.locator('.modal-footer .btn-primary').click()
    await expect(page.locator('.modal-content')).not.toBeVisible()
    await expect(page.getByRole('cell', { name: policyName })).toBeVisible()
  })

  test('закрыть модал по кнопке Отмена', async ({ page }) => {
    await page.getByRole('button', { name: /создать политику/i }).click()
    await expect(page.locator('.modal-content')).toBeVisible()
    await page.locator('.modal-footer .btn-secondary').click()
    await expect(page.locator('.modal-content')).not.toBeVisible()
  })

  test('модал запуска импорта открывается', async ({ page }) => {
    // Создаём политику для теста
    const policyName = `Run-Policy-${Date.now()}`
    await page.getByRole('button', { name: /создать политику/i }).click()
    await page.locator('.modal-content input[placeholder*="Import"]').fill(policyName)
    await page.locator('.modal-content input[placeholder*="jira"]').fill('run-test-system')
    await page.locator('.modal-content input[placeholder*="user_email"]').fill('user_email')
    await page.locator('.modal-content input[placeholder*="task_code"]').fill('task_id')
    await page.locator('.modal-content input[placeholder*="work_type"]').fill('work_type')
    await page.locator('.modal-content input[placeholder*="worked_date"]').fill('date')
    await page.locator('.modal-content input[placeholder*="duration_hours"]').fill('hours')
    await page.locator('.modal-content input[placeholder*="entry_id"]').fill('id')
    await page.locator('.modal-footer .btn-primary').click()
    await expect(page.locator('.modal-content')).not.toBeVisible()

    // Открываем запуск импорта
    const row = page.getByRole('row', { name: new RegExp(policyName) })
    await row.getByTitle('Запустить импорт').click()
    await expect(page.locator('.modal-title')).toContainText('Запустить импорт')
    await expect(page.locator('.modal-content')).toBeVisible()
  })
})

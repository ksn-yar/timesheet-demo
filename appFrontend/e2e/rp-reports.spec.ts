import { test, expect } from '@playwright/test'
import { loginAs, gotoAndReady } from './helpers'

test.describe('Reporting / Отчёты', () => {
  test.beforeEach(async ({ page }) => {
    await loginAs(page)
    await gotoAndReady(page, '/rp/reports')
  })

  test('страница отображается, таблица видна', async ({ page }) => {
    await expect(page.getByRole('heading', { name: 'Отчёты' })).toBeVisible()
    await expect(page.getByRole('table')).toBeVisible()
  })

  test('кнопка "Создать отчёт" видна', async ({ page }) => {
    await expect(page.getByRole('button', { name: /создать отчёт/i })).toBeVisible()
  })

  test('открытие модала создания отчёта', async ({ page }) => {
    await page.getByRole('button', { name: /создать отчёт/i }).click()
    await expect(page.locator('.modal-content')).toBeVisible()
    await expect(page.locator('.modal-title')).toContainText('Создать отчёт')
  })

  test('создать отчёт без названия → показывается ошибка', async ({ page }) => {
    await page.getByRole('button', { name: /создать отчёт/i }).click()
    await page.locator('.modal-footer .btn-primary').click()
    await expect(page.locator('.modal-content .alert-danger')).toBeVisible()
  })

  test('создать отчёт без группировки → показывается ошибка', async ({ page }) => {
    await page.getByRole('button', { name: /создать отчёт/i }).click()
    await page.locator('.modal-content input[type="text"]').fill('Тест без группировки')
    await page.locator('.modal-content input[type="date"]').first().fill('2026-01-01')
    await page.locator('.modal-content input[type="date"]').last().fill('2026-01-31')
    // Снимаем все чекбоксы группировки
    for (const cb of await page.locator('.modal-content input[type="checkbox"]').all()) {
      if (await cb.isChecked()) await cb.uncheck()
    }
    await page.locator('.modal-footer .btn-primary').click()
    await expect(page.locator('.modal-content .alert-danger')).toBeVisible()
  })

  test('создать отчёт → появляется в таблице', async ({ page }) => {
    const name = `Отчёт-${Date.now()}`

    await page.getByRole('button', { name: /создать отчёт/i }).click()
    await page.locator('.modal-content input[type="text"]').fill(name)
    await page.locator('.modal-content input[type="date"]').first().fill('2026-01-01')
    await page.locator('.modal-content input[type="date"]').last().fill('2026-01-31')

    await page.locator('.modal-footer .btn-primary').click()
    await expect(page.locator('.modal-content')).not.toBeVisible()
    await expect(page.getByRole('cell', { name })).toBeVisible()
  })

  test('закрыть модал по кнопке Отмена', async ({ page }) => {
    await page.getByRole('button', { name: /создать отчёт/i }).click()
    await expect(page.locator('.modal-content')).toBeVisible()
    await page.locator('.modal-footer .btn-secondary').click()
    await expect(page.locator('.modal-content')).not.toBeVisible()
  })

  test('просмотр данных отчёта открывает модал', async ({ page }) => {
    const name = `Просмотр-${Date.now()}`
    await page.getByRole('button', { name: /создать отчёт/i }).click()
    await page.locator('.modal-content input[type="text"]').fill(name)
    await page.locator('.modal-content input[type="date"]').first().fill('2026-01-01')
    await page.locator('.modal-content input[type="date"]').last().fill('2026-01-31')
    await page.locator('.modal-footer .btn-primary').click()
    await expect(page.locator('.modal-content')).not.toBeVisible()

    const row = page.getByRole('row', { name: new RegExp(name) })
    await row.getByTitle('Просмотреть данные').click()
    await expect(page.locator('.modal-content')).toBeVisible()
    await expect(page.locator('.modal-title')).toContainText(name)
  })

  test('выбор отчёта → кнопка экспорта появляется', async ({ page }) => {
    const name = `Экспорт-${Date.now()}`
    await page.getByRole('button', { name: /создать отчёт/i }).click()
    await page.locator('.modal-content input[type="text"]').fill(name)
    await page.locator('.modal-content input[type="date"]').first().fill('2026-01-01')
    await page.locator('.modal-content input[type="date"]').last().fill('2026-01-31')
    await page.locator('.modal-footer .btn-primary').click()
    await expect(page.locator('.modal-content')).not.toBeVisible()

    const row = page.getByRole('row', { name: new RegExp(name) })
    await row.locator('input[type="checkbox"]').check()
    await expect(page.getByRole('button', { name: /экспорт/i })).toBeVisible()
  })

  test('фильтр по названию работает', async ({ page }) => {
    const input = page.locator('input[placeholder*="Название"]')
    await input.fill('несуществующий отчёт xyz')
    await page.keyboard.press('Enter')
    await expect(page.getByRole('table')).toBeVisible()
  })
})

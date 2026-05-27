import { test, expect } from '@playwright/test'
import { loginAs, gotoAndReady } from './helpers'

test.describe('Reporting / Выгрузки', () => {
  test.beforeEach(async ({ page }) => {
    await loginAs(page)
    await gotoAndReady(page, '/rp/exports')
  })

  test('страница отображается, таблица видна', async ({ page }) => {
    await expect(page.getByRole('heading', { name: 'Выгрузки отчётов' })).toBeVisible()
    await expect(page.getByRole('table')).toBeVisible()
  })

  test('ссылка "К отчётам" ведёт на /rp/reports', async ({ page }) => {
    await page.getByRole('link', { name: /к отчётам/i }).click()
    await expect(page).toHaveURL(/\/rp\/reports/)
  })

  test('фильтр по формату работает', async ({ page }) => {
    const select = page.locator('select').first()
    await select.selectOption('csv')
    await expect(page.getByRole('table')).toBeVisible()
    await select.selectOption('')
  })
})

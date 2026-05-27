import { test, expect } from '@playwright/test'
import { loginAs, gotoAndReady } from './helpers'

test.describe('WorkCatalog / Роли исполнителей', () => {
  test.beforeEach(async ({ page }) => {
    await loginAs(page)
    await gotoAndReady(page, '/wc/roles')
  })

  test('страница отображается, таблица видна', async ({ page }) => {
    await expect(page.getByRole('heading', { name: 'Роли исполнителей' })).toBeVisible()
    await expect(page.getByRole('table')).toBeVisible()
  })

  test('создать роль → появляется в таблице', async ({ page }) => {
    const name = `Тест-Role-${Date.now()}`

    await page.getByRole('button', { name: /создать/i }).click()
    await expect(page.locator('.modal-content')).toBeVisible()

    await page.locator('.modal-content input[type="text"]').fill(name)
    await page.locator('.modal-footer .btn-primary').click()

    await expect(page.locator('.modal-content')).not.toBeVisible()
    await expect(page.getByRole('cell', { name })).toBeVisible()
  })

  test('создать с пустым названием → показывается ошибка', async ({ page }) => {
    await page.getByRole('button', { name: /создать/i }).click()
    await expect(page.locator('.modal-content')).toBeVisible()
    await page.locator('.modal-footer .btn-primary').click()
    await expect(page.locator('.modal-content .alert-danger')).toBeVisible()
  })

  test('удалить роль → исчезает из таблицы', async ({ page }) => {
    const name = `Del-Role-${Date.now()}`

    await page.getByRole('button', { name: /создать/i }).click()
    await page.locator('.modal-content input[type="text"]').fill(name)
    await page.locator('.modal-footer .btn-primary').click()
    await expect(page.locator('.modal-content')).not.toBeVisible()

    const row = page.getByRole('row', { name: new RegExp(name) })
    await row.getByTitle('Удалить').click()
    await expect(page.locator('.modal-content')).toBeVisible()
    await page.locator('.modal-footer .btn-danger').click()

    await expect(page.locator('.modal-content')).not.toBeVisible()
    await expect(page.getByRole('cell', { name })).not.toBeVisible()
  })
})

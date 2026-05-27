import { test, expect } from '@playwright/test'
import { loginAs, gotoAndReady } from './helpers'

test.describe('WorkCatalog / Виды работ', () => {
  test.beforeEach(async ({ page }) => {
    await loginAs(page)
    await gotoAndReady(page, '/wc/works')
  })

  test('страница отображается, таблица видна', async ({ page }) => {
    await expect(page.getByRole('heading', { name: 'Виды работ' })).toBeVisible()
    await expect(page.getByRole('table')).toBeVisible()
  })

  test('кнопка "Создать" видна для Admin', async ({ page }) => {
    await expect(page.getByRole('button', { name: /создать/i })).toBeVisible()
  })

  test('создать вид работ → появляется в таблице', async ({ page }) => {
    const name = `Тест-Work-${Date.now()}`

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

  test('создать дубликат → ошибка от API', async ({ page }) => {
    const name = `Dup-Work-${Date.now()}`

    // Первое создание
    await page.getByRole('button', { name: /создать/i }).click()
    await page.locator('.modal-content input[type="text"]').fill(name)
    await page.locator('.modal-footer .btn-primary').click()
    await expect(page.locator('.modal-content')).not.toBeVisible()

    // Второе создание с тем же именем
    await page.getByRole('button', { name: /создать/i }).click()
    await page.locator('.modal-content input[type="text"]').fill(name)
    await page.locator('.modal-footer .btn-primary').click()
    await expect(page.locator('.modal-content .alert-danger')).toBeVisible()
  })

  test('удалить вид работ → исчезает из таблицы', async ({ page }) => {
    const name = `Del-Work-${Date.now()}`

    // Создаём запись
    await page.getByRole('button', { name: /создать/i }).click()
    await page.locator('.modal-content input[type="text"]').fill(name)
    await page.locator('.modal-footer .btn-primary').click()
    await expect(page.locator('.modal-content')).not.toBeVisible()

    // Удаляем её
    const row = page.getByRole('row', { name: new RegExp(name) })
    await row.getByTitle('Удалить').click()
    await expect(page.locator('.modal-content')).toBeVisible()
    await page.locator('.modal-footer .btn-danger').click()

    await expect(page.locator('.modal-content')).not.toBeVisible()
    await expect(page.getByRole('cell', { name })).not.toBeVisible()
  })
})

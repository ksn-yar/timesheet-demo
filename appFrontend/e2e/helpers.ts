import { type Page, expect } from '@playwright/test'

export const ADMIN = { email: 'admin@example.com', password: 'admin' }

export async function loginAs(page: Page, user = ADMIN) {
  const resp = await page.request.post('/api/auth/login', {
    data: { email: user.email, password: user.password },
  })
  const { token, refresh_token } = await resp.json() as { token: string; refresh_token: string }

  await page.context().addCookies([
    { name: 'access_token', value: token, domain: 'localhost', path: '/' },
    { name: 'refresh_token', value: refresh_token, domain: 'localhost', path: '/' },
  ])
}

/** Переходит на страницу и ждёт пока таблица станет видна — признак завершения гидрации и загрузки данных. */
export async function gotoAndReady(page: Page, path: string) {
  await page.goto(path)
  await expect(page.getByRole('table')).toBeVisible({ timeout: 15000 })
}

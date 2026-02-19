import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import Home from '../../../app/page'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'

describe('Home page', () => {
  const renderWithClient = (ui: React.ReactElement) => {
    const queryClient = new QueryClient()
    return render(<QueryClientProvider client={queryClient}>{ui}</QueryClientProvider>)
  }

  it('renders auth screen by default', () => {
    renderWithClient(<Home />)
    expect(screen.getByText('Welcome back')).toBeInTheDocument()
  })

  it('switches to register mode', async () => {
    renderWithClient(<Home />)
    await userEvent.click(screen.getByRole('button', { name: /need an account/i }))
    expect(screen.getByText('Create your account')).toBeInTheDocument()
  })
})

import { useState } from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function PaymentLinksIndex() {
    const { auth, flash } = usePage().props;
    const [createdLink, setCreatedLink] = useState(null);

    const { data, setData, post, processing, errors, reset } = useForm({
        amount: '',
        currency: 'INR',
        description: '',
        customer_name: '',
        customer_email: '',
        customer_contact: '',
        reference_id: '',
        callback_url: '',
        callback_method: 'get',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/payment-links', {
            onSuccess: (page) => {
                if (page.props.flash?.payment_link) {
                    setCreatedLink(page.props.flash.payment_link);
                }
                reset();
            },
        });
    };

    if (!auth.user.razorpay_configured) {
        return (
            <AuthenticatedLayout>
                <Head title="Payment Links" />
                <div className="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-6">
                    <h3 className="text-lg font-medium text-yellow-800 dark:text-yellow-200">
                        Razorpay Not Configured
                    </h3>
                    <p className="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                        Please configure your Razorpay credentials in{' '}
                        <a href="/settings" className="underline font-medium">
                            Settings
                        </a>{' '}
                        before creating payment links.
                    </p>
                </div>
            </AuthenticatedLayout>
        );
    }

    return (
        <AuthenticatedLayout>
            <Head title="Payment Links" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                        Create Payment Link
                    </h1>
                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Generate Razorpay payment links for your customers.
                    </p>
                </div>

                {flash?.success && (
                    <div className="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                        <p className="text-sm text-green-700 dark:text-green-300">
                            {flash.success}
                        </p>
                    </div>
                )}

                {createdLink && (
                    <div className="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6">
                        <h3 className="text-lg font-medium text-blue-800 dark:text-blue-200 mb-4">
                            Payment Link Created
                        </h3>
                        <div className="space-y-2 text-sm">
                            <p>
                                <span className="font-medium">Link ID:</span>{' '}
                                <code className="bg-blue-100 dark:bg-blue-800 px-1 rounded">
                                    {createdLink.id}
                                </code>
                            </p>
                            <p>
                                <span className="font-medium">Short URL:</span>{' '}
                                <a
                                    href={createdLink.short_url}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="text-blue-600 dark:text-blue-400 underline"
                                >
                                    {createdLink.short_url}
                                </a>
                            </p>
                            <p>
                                <span className="font-medium">Amount:</span>{' '}
                                {(createdLink.amount / 100).toFixed(2)} {createdLink.currency}
                            </p>
                            <p>
                                <span className="font-medium">Status:</span>{' '}
                                <span className="capitalize">{createdLink.status}</span>
                            </p>
                        </div>
                        <button
                            onClick={() => setCreatedLink(null)}
                            className="mt-4 text-sm text-blue-600 dark:text-blue-400 underline"
                        >
                            Dismiss
                        </button>
                    </div>
                )}

                <form onSubmit={handleSubmit} className="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Amount (in paise) *
                            </label>
                            <input
                                type="number"
                                value={data.amount}
                                onChange={(e) => setData('amount', e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white"
                                placeholder="10000 (for ₹100)"
                                required
                            />
                            {errors.amount && (
                                <p className="mt-1 text-sm text-red-600">{errors.amount}</p>
                            )}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Currency *
                            </label>
                            <select
                                value={data.currency}
                                onChange={(e) => setData('currency', e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white"
                            >
                                <option value="INR">INR</option>
                                <option value="USD">USD</option>
                                <option value="EUR">EUR</option>
                            </select>
                        </div>

                        <div className="md:col-span-2">
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Description *
                            </label>
                            <input
                                type="text"
                                value={data.description}
                                onChange={(e) => setData('description', e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white"
                                placeholder="Payment for order #123"
                                required
                            />
                            {errors.description && (
                                <p className="mt-1 text-sm text-red-600">{errors.description}</p>
                            )}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Customer Name
                            </label>
                            <input
                                type="text"
                                value={data.customer_name}
                                onChange={(e) => setData('customer_name', e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white"
                            />
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Customer Email
                            </label>
                            <input
                                type="email"
                                value={data.customer_email}
                                onChange={(e) => setData('customer_email', e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white"
                            />
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Customer Phone
                            </label>
                            <input
                                type="text"
                                value={data.customer_contact}
                                onChange={(e) => setData('customer_contact', e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white"
                            />
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Reference ID
                            </label>
                            <input
                                type="text"
                                value={data.reference_id}
                                onChange={(e) => setData('reference_id', e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white"
                                placeholder="Your internal reference"
                            />
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Callback URL
                            </label>
                            <input
                                type="url"
                                value={data.callback_url}
                                onChange={(e) => setData('callback_url', e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white"
                                placeholder="https://yoursite.com/callback"
                            />
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Callback Method
                            </label>
                            <select
                                value={data.callback_method}
                                onChange={(e) => setData('callback_method', e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white"
                            >
                                <option value="get">GET</option>
                                <option value="post">POST</option>
                            </select>
                        </div>
                    </div>

                    {errors.razorpay && (
                        <div className="mt-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-md">
                            <p className="text-sm text-red-700 dark:text-red-300">{errors.razorpay}</p>
                        </div>
                    )}

                    <div className="mt-6">
                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full sm:w-auto px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {processing ? 'Creating...' : 'Create Payment Link'}
                        </button>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}

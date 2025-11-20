import { useState } from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function OrdersIndex() {
    const { auth, flash } = usePage().props;
    const [createdOrder, setCreatedOrder] = useState(null);

    const { data, setData, post, processing, errors, reset } = useForm({
        amount: '',
        currency: 'INR',
        receipt: '',
        partial_payment: false,
        first_payment_min_amount: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/orders', {
            onSuccess: (page) => {
                if (page.props.flash?.order) {
                    setCreatedOrder(page.props.flash.order);
                }
                reset();
            },
        });
    };

    if (!auth.user.razorpay_configured) {
        return (
            <AuthenticatedLayout>
                <Head title="Orders" />
                <div className="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-6">
                    <h3 className="text-lg font-medium text-yellow-800 dark:text-yellow-200">
                        Razorpay Not Configured
                    </h3>
                    <p className="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                        Please configure your Razorpay credentials in{' '}
                        <a href="/settings" className="underline font-medium">
                            Settings
                        </a>{' '}
                        before creating orders.
                    </p>
                </div>
            </AuthenticatedLayout>
        );
    }

    return (
        <AuthenticatedLayout>
            <Head title="Orders" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                        Create Order
                    </h1>
                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Generate Razorpay order IDs for payment processing.
                    </p>
                </div>

                {flash?.success && (
                    <div className="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                        <p className="text-sm text-green-700 dark:text-green-300">
                            {flash.success}
                        </p>
                    </div>
                )}

                {createdOrder && (
                    <div className="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6">
                        <h3 className="text-lg font-medium text-blue-800 dark:text-blue-200 mb-4">
                            Order Created
                        </h3>
                        <div className="space-y-2 text-sm">
                            <p>
                                <span className="font-medium">Order ID:</span>{' '}
                                <code className="bg-blue-100 dark:bg-blue-800 px-2 py-1 rounded">
                                    {createdOrder.id}
                                </code>
                            </p>
                            <p>
                                <span className="font-medium">Amount:</span>{' '}
                                {(createdOrder.amount / 100).toFixed(2)} {createdOrder.currency}
                            </p>
                            <p>
                                <span className="font-medium">Receipt:</span>{' '}
                                {createdOrder.receipt || 'N/A'}
                            </p>
                            <p>
                                <span className="font-medium">Status:</span>{' '}
                                <span className="capitalize">{createdOrder.status}</span>
                            </p>
                        </div>
                        <div className="mt-4">
                            <button
                                onClick={() => navigator.clipboard.writeText(createdOrder.id)}
                                className="text-sm text-blue-600 dark:text-blue-400 underline mr-4"
                            >
                                Copy Order ID
                            </button>
                            <button
                                onClick={() => setCreatedOrder(null)}
                                className="text-sm text-blue-600 dark:text-blue-400 underline"
                            >
                                Dismiss
                            </button>
                        </div>
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

                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Receipt
                            </label>
                            <input
                                type="text"
                                value={data.receipt}
                                onChange={(e) => setData('receipt', e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white"
                                placeholder="receipt_123"
                                maxLength={40}
                            />
                            <p className="mt-1 text-xs text-gray-500">Max 40 characters</p>
                        </div>

                        <div className="flex items-center">
                            <input
                                type="checkbox"
                                id="partial_payment"
                                checked={data.partial_payment}
                                onChange={(e) => setData('partial_payment', e.target.checked)}
                                className="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                            />
                            <label htmlFor="partial_payment" className="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                Allow Partial Payment
                            </label>
                        </div>

                        {data.partial_payment && (
                            <div className="md:col-span-2">
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Minimum First Payment Amount (in paise)
                                </label>
                                <input
                                    type="number"
                                    value={data.first_payment_min_amount}
                                    onChange={(e) => setData('first_payment_min_amount', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white"
                                    placeholder="5000 (for ₹50)"
                                />
                            </div>
                        )}
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
                            {processing ? 'Creating...' : 'Create Order'}
                        </button>
                    </div>
                </form>

                <div className="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-6">
                    <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-4">
                        How to use Order IDs
                    </h3>
                    <ol className="list-decimal list-inside space-y-2 text-sm text-gray-600 dark:text-gray-400">
                        <li>Create an order with the amount and currency</li>
                        <li>Copy the generated Order ID</li>
                        <li>Use the Order ID with Razorpay Checkout on your frontend</li>
                        <li>Razorpay will handle the payment collection</li>
                        <li>Verify the payment using Razorpay webhooks</li>
                    </ol>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

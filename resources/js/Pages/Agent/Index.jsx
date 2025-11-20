import { useState } from 'react';
import { Head, useForm, usePage, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function AgentIndex({ conversations, workflows, connections }) {
    const { auth, flash } = usePage().props;
    const [showConnectionModal, setShowConnectionModal] = useState(false);

    const connectionForm = useForm({
        name: '',
        driver: 'mysql',
        host: 'localhost',
        port: '3306',
        database: '',
        username: '',
        password: '',
    });

    const conversationForm = useForm({
        database_connection_id: '',
    });

    const handleCreateConnection = (e) => {
        e.preventDefault();
        connectionForm.post('/agent/connections', {
            onSuccess: () => {
                setShowConnectionModal(false);
                connectionForm.reset();
            },
        });
    };

    const handleCreateConversation = (e) => {
        e.preventDefault();
        conversationForm.post('/agent/conversations');
    };

    const handleTestConnection = (connectionId) => {
        router.post(`/agent/connections/${connectionId}/test`);
    };

    if (!auth.user.ai_configured) {
        return (
            <AuthenticatedLayout>
                <Head title="AI Agent" />
                <div className="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-6">
                    <h3 className="text-lg font-medium text-yellow-800 dark:text-yellow-200">
                        AI Provider Not Configured
                    </h3>
                    <p className="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                        Please configure your AI provider credentials in{' '}
                        <a href="/settings" className="underline font-medium">
                            Settings
                        </a>{' '}
                        before using the AI Agent.
                    </p>
                </div>
            </AuthenticatedLayout>
        );
    }

    return (
        <AuthenticatedLayout>
            <Head title="AI Agent" />

            <div className="space-y-6">
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                            AI Database Agent
                        </h1>
                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Describe your database requirements and let AI design and implement schema changes.
                        </p>
                    </div>
                </div>

                {flash?.success && (
                    <div className="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                        <p className="text-sm text-green-700 dark:text-green-300">
                            {flash.success}
                        </p>
                    </div>
                )}

                {/* Database Connections */}
                <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                    <div className="flex justify-between items-center mb-4">
                        <h2 className="text-lg font-medium text-gray-900 dark:text-white">
                            Database Connections
                        </h2>
                        <button
                            onClick={() => setShowConnectionModal(true)}
                            className="px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700"
                        >
                            Add Connection
                        </button>
                    </div>

                    {connections.length === 0 ? (
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                            No database connections configured. Add one to start using the AI agent.
                        </p>
                    ) : (
                        <div className="space-y-3">
                            {connections.map((connection) => (
                                <div
                                    key={connection.id}
                                    className="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-md"
                                >
                                    <div>
                                        <p className="font-medium text-gray-900 dark:text-white">
                                            {connection.name}
                                        </p>
                                        <p className="text-sm text-gray-500 dark:text-gray-400">
                                            {connection.driver}://{connection.host}:{connection.port}/{connection.database}
                                        </p>
                                    </div>
                                    <button
                                        onClick={() => handleTestConnection(connection.id)}
                                        className="px-3 py-1 text-sm text-indigo-600 dark:text-indigo-400 hover:underline"
                                    >
                                        Test
                                    </button>
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                {/* Start New Conversation */}
                <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                    <h2 className="text-lg font-medium text-gray-900 dark:text-white mb-4">
                        Start New Conversation
                    </h2>
                    <form onSubmit={handleCreateConversation} className="flex gap-4">
                        <select
                            value={conversationForm.data.database_connection_id}
                            onChange={(e) => conversationForm.setData('database_connection_id', e.target.value)}
                            className="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white"
                        >
                            <option value="">Select a database (optional)</option>
                            {connections.map((connection) => (
                                <option key={connection.id} value={connection.id}>
                                    {connection.name}
                                </option>
                            ))}
                        </select>
                        <button
                            type="submit"
                            disabled={conversationForm.processing}
                            className="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50"
                        >
                            Start Chat
                        </button>
                    </form>
                </div>

                {/* Recent Conversations */}
                <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                    <h2 className="text-lg font-medium text-gray-900 dark:text-white mb-4">
                        Recent Conversations
                    </h2>

                    {conversations.length === 0 ? (
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                            No conversations yet. Start a new chat to begin.
                        </p>
                    ) : (
                        <div className="space-y-3">
                            {conversations.map((conversation) => (
                                <a
                                    key={conversation.id}
                                    href={`/agent/conversations/${conversation.id}`}
                                    className="block p-4 bg-gray-50 dark:bg-gray-700 rounded-md hover:bg-gray-100 dark:hover:bg-gray-600"
                                >
                                    <div className="flex justify-between items-start">
                                        <div>
                                            <p className="font-medium text-gray-900 dark:text-white">
                                                Conversation #{conversation.id}
                                            </p>
                                            <p className="text-sm text-gray-500 dark:text-gray-400">
                                                {conversation.database_connection?.name || 'No database selected'}
                                            </p>
                                            <p className="text-xs text-gray-400 mt-1">
                                                {conversation.messages?.length || 0} messages
                                            </p>
                                        </div>
                                        <span className="text-xs text-gray-400">
                                            {new Date(conversation.created_at).toLocaleDateString()}
                                        </span>
                                    </div>
                                </a>
                            ))}
                        </div>
                    )}
                </div>

                {/* Workflows */}
                {workflows.length > 0 && (
                    <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                        <h2 className="text-lg font-medium text-gray-900 dark:text-white mb-4">
                            Recent Workflows
                        </h2>
                        <div className="space-y-3">
                            {workflows.map((workflow) => (
                                <div
                                    key={workflow.id}
                                    className="p-4 bg-gray-50 dark:bg-gray-700 rounded-md"
                                >
                                    <div className="flex justify-between items-start">
                                        <div>
                                            <p className="font-medium text-gray-900 dark:text-white">
                                                {workflow.name}
                                            </p>
                                            <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                                {workflow.description?.substring(0, 100)}...
                                            </p>
                                        </div>
                                        <span className={`px-2 py-1 text-xs rounded-full ${
                                            workflow.status === 'completed'
                                                ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                                : workflow.status === 'failed'
                                                ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                                                : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'
                                        }`}>
                                            {workflow.status}
                                        </span>
                                    </div>
                                    {workflow.schema_proposals?.length > 0 && (
                                        <p className="text-xs text-gray-400 mt-2">
                                            {workflow.schema_proposals.length} proposal(s)
                                        </p>
                                    )}
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </div>

            {/* Connection Modal */}
            {showConnectionModal && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div className="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-md">
                        <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-4">
                            Add Database Connection
                        </h3>
                        <form onSubmit={handleCreateConnection} className="space-y-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Connection Name *
                                </label>
                                <input
                                    type="text"
                                    value={connectionForm.data.name}
                                    onChange={(e) => connectionForm.setData('name', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white"
                                    required
                                />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Driver *
                                </label>
                                <select
                                    value={connectionForm.data.driver}
                                    onChange={(e) => connectionForm.setData('driver', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white"
                                >
                                    <option value="mysql">MySQL</option>
                                    <option value="pgsql">PostgreSQL</option>
                                    <option value="sqlite">SQLite</option>
                                    <option value="sqlsrv">SQL Server</option>
                                </select>
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Host
                                    </label>
                                    <input
                                        type="text"
                                        value={connectionForm.data.host}
                                        onChange={(e) => connectionForm.setData('host', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Port
                                    </label>
                                    <input
                                        type="number"
                                        value={connectionForm.data.port}
                                        onChange={(e) => connectionForm.setData('port', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white"
                                    />
                                </div>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Database *
                                </label>
                                <input
                                    type="text"
                                    value={connectionForm.data.database}
                                    onChange={(e) => connectionForm.setData('database', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white"
                                    required
                                />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Username
                                </label>
                                <input
                                    type="text"
                                    value={connectionForm.data.username}
                                    onChange={(e) => connectionForm.setData('username', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white"
                                />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Password
                                </label>
                                <input
                                    type="password"
                                    value={connectionForm.data.password}
                                    onChange={(e) => connectionForm.setData('password', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white"
                                />
                            </div>

                            <div className="flex justify-end gap-3 mt-6">
                                <button
                                    type="button"
                                    onClick={() => setShowConnectionModal(false)}
                                    className="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    disabled={connectionForm.processing}
                                    className="px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700 disabled:opacity-50"
                                >
                                    {connectionForm.processing ? 'Creating...' : 'Create'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}

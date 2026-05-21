const { ethers } = require("ethers");

async function getBnbBalance(address) {
    // Connect to BSC mainnet
    const provider = new ethers.providers.JsonRpcProvider("https://bsc-dataseed.binance.org/");
    
    // Get the balance
    const balance = await provider.getBalance(address);
    
    // Convert balance from Wei to BNB
    const bnbBalance = ethers.utils.formatEther(balance);
    
    // Format the balance to three decimal places
    const formattedBnbBalance = parseFloat(bnbBalance).toFixed(3);
    
    return formattedBnbBalance;
}

async function getPolygonBalance(address) {
    // Connect to Polygon mainnet
    const provider = new ethers.providers.JsonRpcProvider("https://polygon-rpc.com/");
    
    // Get the balance
    const balance = await provider.getBalance(address);
    
    // Convert balance from Wei to MATIC
    const maticBalance = ethers.utils.formatEther(balance);
    
    // Format the balance to three decimal places
    const formattedMaticBalance = parseFloat(maticBalance).toFixed(3);
    
    return formattedMaticBalance;
}

// async function getEtherBalance(address) {
//     // Connect to Ethereum mainnet (Infura or other node providers)
//     const provider = new ethers.providers.JsonRpcProvider("https://mainnet.infura.io/v3/2166a0ecead542ff9b1e9369ca7085dc");
    
//     // Get the balance
//     const balance = await provider.getBalance(address);
    
//     // Convert balance from Wei to Ether
//     const etherBalance = ethers.utils.formatEther(balance);
    
//     // Format the balance to three decimal places
//     const formattedEtherBalance = parseFloat(etherBalance).toFixed(3);
    
//     return formattedEtherBalance;
// }

async function getUsdtBalance(walletAddress) {
    // BNBUSDT contract address (USDT contract on BSC)
    const BNBUSDT_TOKEN_ADDRESS = '0x55d398326f99059fF775485246999027B3197955'; // Example contract address for USDT on BSC

    // Define ABI for BEP-20 token interaction (simplified)
    const tokenAbi = ['function balanceOf(address owner) view returns (uint256)'];

    // Initialize contract
    const provider = new ethers.providers.JsonRpcProvider("https://bsc-dataseed.binance.org/");
    const bnbusdtContract = new ethers.Contract(BNBUSDT_TOKEN_ADDRESS, tokenAbi, provider);

    // Fetch the BNBUSDT balance
    const balance = await bnbusdtContract.balanceOf(walletAddress);

    // Format the balance to human-readable format (USDT has 18 decimals on BSC)
    const formattedBalance = ethers.utils.formatUnits(balance, 18);

    // Split the formatted balance at the decimal point and take the first 3 digits after the decimal
    const parts = formattedBalance.split('.');
    const roundedBalance = parts[0] + '.' + (parts[1] ? parts[1].slice(0, 3) : '000');

    return roundedBalance;
}

async function main() {
    // Replace with the address you want to check
    const address = process.argv[2];

    // Get balances
    const bnbBalance = await getBnbBalance(address);
    const maticBalance = await getPolygonBalance(address);
    //const etherBalance = await getEtherBalance(address);
    const usdtBalance = await getUsdtBalance(address);

    // Output the balances in JSON format
    const result = {
        bnb: bnbBalance,
        matic: maticBalance,
        //ether: etherBalance,
        usdt: usdtBalance,

    };

    console.log(JSON.stringify(result, null, 2));
}

// Run the main function
main().catch(error => {
    console.error('Error:', error);
});

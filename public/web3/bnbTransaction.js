const { ethers } = require('ethers');
const privateKey = process.argv[2];
const toAddress = process.argv[3];
const amountInBNB = process.argv[4];

if (!privateKey || !toAddress || !amountInBNB) {
  console.error('Usage: node transfer.js <private_key> <recipient_address> <amount_in_BNB>');
  process.exit(1);
}

const provider = new ethers.providers.JsonRpcProvider('https://bsc-dataseed.binance.org/');
const wallet = new ethers.Wallet(privateKey, provider);

async function sendBNB() {
  try {
    const tx = {
      to: toAddress,
      value: ethers.utils.parseEther(amountInBNB),
      gasPrice: await provider.getGasPrice(),
      gasLimit: 21000,
    };

    const txResponse = await wallet.sendTransaction(tx);
    console.log(txResponse.hash);
  } catch (error) {
    console.log('failed to transaction');
  }
}

sendBNB();
